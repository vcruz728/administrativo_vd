<?php

namespace App\Http\Controllers\Oficios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Oficios\Oficio;
use App\Models\Oficios\Copia;
use App\Models\Oficios\ArchivoOficio;
use App\Models\Oficios\RespuestaOficio;
use App\Models\Oficios\Nuevo as NuevoOficio;
use App\Models\Oficios\DestinatarioOficio;
use App\Models\Catalogos\Des;
use App\Models\Catalogos\Areas;
use App\Models\Catalogos\Procesos;
use App\Models\Catalogos\DestinatarioExterno;
use App\Models\Directorio\Directorio;
use App\Models\Variables;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;
use App\Mail\Oficios\Nuevo;
use App\Mail\Oficios\Rechazo;
use App\Mail\Oficios\ColaboradorRespuesta;
use App\Mail\Oficios\RechazoRespuestaJefe;
use App\Mail\Oficios\AceptaRespuestaJefe;
use App\Mail\Oficios\RespuestaOficio as MailRespuestaOficio;
use App\Mail\Oficios\RechazoRespuestaFinal;
use App\Mail\Oficios\Enviado;
use DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\User;

class OficioController extends Controller
{
	public function index()
	{
		$where = "";
		$whereDos = "";
		if (\Auth::user()->rol == 3) {
			$where = " AND area = " . \Auth::user()->id_area;
			$whereDos = " AND id_area = " . \Auth::user()->id_area . " AND revision = 1";
		} else if (\Auth::user()->rol == 4) {
			$where = " AND id_usuario = " . \Auth::user()->id;
			$whereDos = " AND id_usuario = " . \Auth::user()->id;
		}


		$oficios = Oficio::select(
			DB::raw("CASE 
        WHEN DATEDIFF (MINUTE, convert(varchar, oficios.created_at, 120), convert(varchar, oficios.fecha_respuesta, 120)) < cat_areas.minutos_oficio 
             AND oficios.fecha_respuesta IS NOT NULL THEN 1 -- Verde
        WHEN DATEDIFF (MINUTE, convert(varchar, oficios.created_at, 120), convert(varchar, getdate(), 120)) < cat_areas.minutos_oficio 
             AND oficios.fecha_respuesta IS NULL THEN 2 -- Amarillo
        WHEN DATEDIFF (MINUTE, convert(varchar, oficios.created_at, 120), convert(varchar, getdate(), 120)) > cat_areas.minutos_oficio 
             AND oficios.fecha_respuesta IS NULL THEN 3 -- Naranja
        WHEN DATEDIFF (MINUTE, convert(varchar, oficios.created_at, 120), convert(varchar, oficios.fecha_respuesta, 120)) > cat_areas.minutos_oficio 
             AND oficios.fecha_respuesta IS NOT NULL THEN 4 -- Rojo
        ELSE 5 END as estatus_valor"),
			DB::raw("CASE 
			WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, oficios.fecha_respuesta, 120) ) < cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NOT NULL THEN '#5fd710'
			WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, getdate(), 120) ) < cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NULL THEN '#f5f233'
			WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, getdate(), 120) ) > cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NULL THEN '#f98200'
			WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, oficios.fecha_respuesta, 120) ) > cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NOT NULL THEN '#ff2d2d'
			ELSE '#000000' END as color"),
			DB::raw("CONCAT( RIGHT('0'+cast(DAY(oficios.created_at) as varchar(2)),2) ,' de ', dbo.fn_GetMonthName (oficios.created_at, 'Spanish'),' de ',YEAR(oficios.created_at),' a las ', CONVERT(VARCHAR(5),oficios.created_at,108)) as f_ingreso"),
			'oficios.id',
			'num_folio',
			'num_oficio',
			DB::raw(" CASE WHEN ingreso = 'Email' THEN num_folio ELSE num_oficio END as numero_oficio "),
			'cat_des.nombre as des',
			'cat_areas.nombre as area',
			'cat_procesos.nombre as proceso',
			'dep_ua',
			'area as id_area',
			DB::raw("coalesce(users.name,'' ) as responsable"),
			'oficios.id_usuario',
			'proceso_impacta',
			'descripcion',
			'oficios.archivo',
			'oficios.descripcion_respuesta',
			'oficios.archivo_respuesta',
			'oficios.descripcion_rechazo',
			'oficios.finalizado',
			'oficios.respuesta',
			'oficios.oficio_final',
			'oficios.descripcion_rechazo_jefe',
			'descripcion_rechazo_final',
			DB::raw("RIGHT(oficios.archivo_respuesta, 3) as extension"),
			'respuestas_oficio.nombre as destinatario',
			't3.total_inicial',
			't4.total_respuesta',
			'respuestas_oficio.respuesta as asunto',
		)
			->join('cat_des', 'cat_des.id', 'oficios.dep_ua')
			->join('cat_areas', 'cat_areas.id', 'oficios.area')
			->leftJoin('cat_procesos', 'cat_procesos.id', 'oficios.proceso_impacta')
			->Leftjoin('users', 'users.id', 'oficios.id_usuario')
			->leftJoin('respuestas_oficio', 'respuestas_oficio.id_oficio', 'oficios.id')
			->leftJoin(DB::raw("(SELECT COUNT(id) as total_inicial, id_oficio_inicial FROM archivos_oficios WHERE id_nuevo_oficio IS NULL AND id_oficio IS NULL GROUP BY id_oficio_inicial ) as t3"), 't3.id_oficio_inicial', 'oficios.id')
			->leftJoin(DB::raw("(SELECT COUNT(id) as total_respuesta, id_oficio  FROM archivos_oficios WHERE id_nuevo_oficio IS  NULL AND id_oficio_inicial IS NULL GROUP BY id_oficio ) as t4"), 't4.id_oficio', 'oficios.id')
			->whereRaw("1=1 $where")
			->orWhere("area", 1)
			->orderBy('oficios.created_at', 'desc')

			->get();


		$procesos = Procesos::getSelForArea(\Auth::user()->id_area);
		$usuarios = User::getSel(\Auth::user()->id_area);

		/** Agregado de destinatarios: total, folios, folio y nombre_desti (excluye eliminados) */
		$destAgg = DB::table('destinatarios_oficio as d')
			->leftJoin('directorios as dir', 'dir.id', '=', 'd.id_usuario')
			->leftJoin('cat_destinatarios_externos as ext', 'ext.id', '=', 'd.id_usuario')
			->whereNull('d.deleted_at')
			->groupBy('d.id_oficio')
			->selectRaw("
        d.id_oficio,
        COUNT(*) AS total_dest,
        STRING_AGG(CAST(d.folio AS varchar(10)), ', ') AS folios,
        MAX(d.folio) AS folio,
        CASE
            WHEN COUNT(*) = 1
                 THEN MAX(CASE WHEN d.tipo_usuario = 1 THEN dir.nombre
                               WHEN d.tipo_usuario = 2 THEN ext.nombre
                          END)
            ELSE 'Multi Destinatario'
        END AS nombre_desti
    ");


		$t3 = DB::table('archivos_oficios as a')
			->whereNull('a.id_oficio_inicial')
			->whereNull('a.id_oficio')
			->groupBy('a.id_nuevo_oficio')
			->selectRaw('COUNT(a.id) AS total_nuevo, a.id_nuevo_oficio');

		/** Consulta principal optimizada */
		$nuevos = NuevoOficio::query()
			->from('nuevos_oficios')
			->select([
				'nuevos_oficios.id',
				'cat_areas.nombre as area',
				'nuevos_oficios.nombre as destinatario',
				'nuevos_oficios.archivo_respuesta',
				'enviado',
				'finalizado',
				'revision',
				'id_usuario',
				'descripcion_rechazo_jefe',
				'descripcion_rechazo_final',
				'archivo',
				DB::raw('COALESCE(respuesta, descripcion) AS respuesta'),
				'masivo',
				// oficio_respuesta: usa el de nuevos_oficios o cae a folio/folios agregados
				DB::raw("
            COALESCE(
                CAST(nuevos_oficios.oficio_respuesta AS VARCHAR(50)),
                CASE WHEN dest.total_dest = 1
                     THEN CAST(dest.folio AS VARCHAR(50))
                     ELSE dest.folios
                END
            ) AS oficio_respuesta
        "),
				DB::raw("COALESCE(dest.nombre_desti, 'Grupal') AS nombre_desti"),
				DB::raw("
            CONCAT(
                RIGHT('0' + CAST(DAY(nuevos_oficios.created_at) AS varchar(2)), 2),
                ' de ', dbo.fn_GetMonthName(nuevos_oficios.created_at, 'Spanish'),
                ' de ', YEAR(nuevos_oficios.created_at),
                ' a las ', CONVERT(VARCHAR(5), nuevos_oficios.created_at, 108)
            ) AS f_ingreso
        "),
				DB::raw('RIGHT(nuevos_oficios.archivo_respuesta, 3) AS extension'),
				't3.total_nuevo',
			])
			->join('cat_areas', 'cat_areas.id', '=', 'nuevos_oficios.id_area')
			->leftJoinSub($t3, 't3', function ($join) {
				$join->on('t3.id_nuevo_oficio', '=', 'nuevos_oficios.id');
			})
			->leftJoinSub($destAgg, 'dest', function ($join) {
				$join->on('dest.id_oficio', '=', 'nuevos_oficios.id');
			})

			->whereRaw("1=1 $whereDos")                // tus filtros dinámicos
			->orderByDesc('nuevos_oficios.created_at')
			->get();

		/** Tu map sigue igual: convierte lista de folios a rangos cuando son muchos */
		$nuevos = $nuevos->map(function ($item) {
			if (!empty($item->oficio_respuesta)) {
				$folios = explode(',', $item->oficio_respuesta);
				$folios = array_map('trim', $folios);
				// Nos quedamos solo con entradas numéricas por si viniera algo raro
				$folios = array_values(array_filter($folios, fn($x) => is_numeric($x)));
				sort($folios, SORT_NUMERIC);

				$rango = [];
				$inicio = $prev = null;

				foreach ($folios as $folio) {
					if ($inicio === null) {
						$inicio = $prev = $folio;
						continue;
					}
					if ($folio == $prev + 1) {
						$prev = $folio;
					} else {
						$rango[] = ($inicio == $prev) ? $inicio : "$inicio-$prev";
						$inicio = $prev = $folio;
					}
				}
				if ($inicio !== null) {
					$rango[] = ($inicio == $prev) ? $inicio : "$inicio-$prev";
				}

				if (count($folios) <= 5) {
					$item->oficio_respuesta = implode(', ', $folios);
				} else {
					$item->oficio_respuesta = implode(', ', $rango);
				}
			}
			return $item;
		});


		if (\Auth::user()->rol == 6) {
			return Inertia::render('Oficios/OficiosAdmin', [
				'status' => session('status'),
				'oficios' => $oficios,
				'usuariosSelect' => $usuarios,
				'procesos' => $procesos,
				'nuevos' => $nuevos
			]);
		} else {
			return Inertia::render('Oficios/MisOficios', [
				'status' => session('status'),
				'oficios' => $oficios,
				'usuariosSelect' => $usuarios,
				'procesos' => $procesos,
				'nuevos' => $nuevos
			]);
		}
	}

	public function indexResp($id)
	{
		$bandera = Oficio::find($id);
		if ($bandera->respuesta > 0 && \Auth::user()->rol == 4) {
			return redirect()->route('dashboard')->withErrors(['error' => 'No cuenta con los permisos suficientes para acceder a la ruta']);
		} else if ($bandera->respuesta == 0 && $bandera->id_usuario !== null && \Auth::user()->rol != 4) {
			return redirect()->route('dashboard')->withErrors(['error' => 'No cuenta con los permisos suficientes para acceder a la ruta']);
		}

		$directorio = Directorio::select('id as value', 'nombre as label')->whereNotIn('id', function ($query) use ($id) {
			$query->select('id_directorio')->from('oficios_copias')->where('id_oficio', $id)->whereNotNull('id_directorio');
		})->orderBy('nombre')->get();

		$directorioAll = Directorio::getSel();

		$copy = Copia::select('id', 'id_oficio', 'id_directorio', 'nombre', 'cargo', 'dependencia')->where('id_oficio', $id)->get();

		$respuesta = RespuestaOficio::select('id', 'id_oficio', 'tipo_destinatario', 'nombre', 'cargo', 'dependencia', 'id_directorio', 'respuesta', 'comentario')
			->where('id_oficio', $id)
			->first();

		$archivos = ArchivoOficio::where('id_oficio', $id)
			->get()
			->map(function ($archivo) {
				$extension = pathinfo($archivo->archivo, PATHINFO_EXTENSION);

				if ($extension == "pdf" || $extension == "jpg" || $extension == "jpeg" || $extension == "png") {
					$url = $archivo->archivo;
				} else {
					$url = asset("files/" . $archivo->archivo);
				}


				return [
					'serverId' => $archivo->id,
					'origin' => 1,
					'source' => $archivo->id,
					'file' => $archivo->archivo,
					'options' => [
						'type' => 'local',
						'file' => [
							'name' => $archivo->nombre,
							'size' => \Storage::disk('files')->exists($archivo->archivo) ? \Storage::disk('files')->size($archivo->archivo) : 0,
							'type' => mime_content_type(\Storage::disk('files')->path($archivo->archivo)),
						],
						'metadata' => [
							'url' => $url,
							'extension' => $extension,
						],
					],
				];
			});

		$externos = DestinatarioExterno::getSel();

		return Inertia::render('Oficios/ResponderOficio', ['status' => session('status'), 'externos' => $externos, 'oficio' => $bandera, 'files' => $archivos, 'directorio' => $directorio, 'copy' => $copy, 'respuesta' => $respuesta, 'directorioAll' => $directorioAll]);
	}

	public function viewResp($id)
	{
		$oficios = Oficio::select(
			'oficios.id',
			'num_folio',
			'num_oficio',
			DB::raw(" CASE WHEN ingreso = 'Email' THEN num_folio ELSE num_oficio END as numero_oficio "),
			'cat_des.nombre as des',
			'cat_areas.nombre as area',
			'cat_procesos.nombre as proceso',
			'dep_ua',
			'area as id_area',
			DB::raw("CONCAT(cat_procesos.nombre, CASE WHEN users.name IS NULL THEN '' ELSE ' / '+users.name END ) as responsable"),
			'oficios.id_usuario',
			'proceso_impacta',
			'descripcion',
			'oficios.archivo',
			'oficios.descripcion_respuesta',
			'oficios.archivo_respuesta',
			'oficios.descripcion_rechazo',
			'oficios.finalizado',
			'oficios.ingreso',
			'respuestas_oficio.comentario',
			'respuestas_oficio.id as id_respuesta',
			'respuestas_oficio.fecha_respuesta'
		)
			->join('cat_des', 'cat_des.id', 'oficios.dep_ua')
			->join('cat_areas', 'cat_areas.id', 'oficios.area')
			->join('cat_procesos', 'cat_procesos.id', 'oficios.proceso_impacta')
			->join('respuestas_oficio', 'respuestas_oficio.id_oficio', 'oficios.id')
			->Leftjoin('users', 'users.id', 'oficios.id_usuario')
			->where('oficios.id', $id)
			->first();

		$archivos = ArchivoOficio::select('id', 'nombre', 'archivo')
			->where('id_oficio', $id)
			->get()
			->map(function ($archivo) {
				$extension = pathinfo($archivo->archivo, PATHINFO_EXTENSION);

				if ($extension == "pdf" || $extension == "jpg" || $extension == "jpeg" || $extension == "png") {
					$tipo = 1;
					$url = $archivo->archivo;
				} else {
					$url = asset("files/" . $archivo->archivo);
					$tipo = 2;
				}

				return [
					'id' => $archivo->id,
					'tipo' => $tipo,
					'url' => $url,
					'nombre' => $archivo->nombre,
					'extension' => $extension,
				];
			});

		return Inertia::render('Oficios/RevisaRespuesta', [
			'status' => session('status'),
			'oficio' => $oficios,
			'archivos' => $archivos,
		]);
	}

	public function viewOficiosResp()
	{
		$oficios = Oficio::select(
			DB::raw("CASE 
        WHEN DATEDIFF (MINUTE, convert(varchar, oficios.created_at, 120), convert(varchar, oficios.fecha_respuesta, 120)) < cat_areas.minutos_oficio 
             AND oficios.fecha_respuesta IS NOT NULL THEN 1 -- Verde
        WHEN DATEDIFF (MINUTE, convert(varchar, oficios.created_at, 120), convert(varchar, getdate(), 120)) < cat_areas.minutos_oficio 
             AND oficios.fecha_respuesta IS NULL THEN 2 -- Amarillo
        WHEN DATEDIFF (MINUTE, convert(varchar, oficios.created_at, 120), convert(varchar, getdate(), 120)) > cat_areas.minutos_oficio 
             AND oficios.fecha_respuesta IS NULL THEN 3 -- Naranja
        WHEN DATEDIFF (MINUTE, convert(varchar, oficios.created_at, 120), convert(varchar, oficios.fecha_respuesta, 120)) > cat_areas.minutos_oficio 
             AND oficios.fecha_respuesta IS NOT NULL THEN 4 -- Rojo
        ELSE 5 END as estatus_valor"),
			DB::raw("CASE 
			WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, oficios.fecha_respuesta, 120) ) < cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NOT NULL THEN '#5fd710'
			WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, getdate(), 120) ) < cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NULL THEN '#f5f233'
			WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, getdate(), 120) ) > cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NULL THEN '#f98200'
			WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, oficios.fecha_respuesta, 120) ) > cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NOT NULL THEN '#ff2d2d'
			ELSE '#000000' END as color"),
			DB::raw("CONCAT( RIGHT('0'+cast(DAY(oficios.created_at) as varchar(2)),2) ,' de ', dbo.fn_GetMonthName (oficios.created_at, 'Spanish'),' de ',YEAR(oficios.created_at),' a las ', CONVERT(VARCHAR(5),oficios.created_at,108)) as f_ingreso"),
			DB::raw(" CASE WHEN ingreso = 'Email' THEN num_folio ELSE num_oficio END as numero_oficio "),
			'oficios.id',
			'num_folio',
			'num_oficio',
			'cat_areas.nombre as area',
			'cat_procesos.nombre as proceso',
			'descripcion',
			'oficios.archivo',
			'oficios.area as id_area',
			'oficios.enviado',
			'oficios.archivo_respuesta',
			'oficios.oficio_final',
			DB::raw("RIGHT(oficios.archivo_respuesta, 3) as extension"),
			'respuestas_oficio.respuesta as asunto',
		)
			->join('cat_areas', 'cat_areas.id', 'oficios.area')
			->leftJoin('cat_procesos', 'cat_procesos.id', 'oficios.proceso_impacta')
			->leftJoin('respuestas_oficio', 'respuestas_oficio.id_oficio', 'oficios.id')
			->where('finalizado', 1)
			->orWhere('area', 1)
			->orderBy('oficios.created_at', 'desc')

			->get();


		$nuevos = NuevoOficio::select(
			'nuevos_oficios.id',
			'cat_areas.nombre as area',
			'nuevos_oficios.nombre as destinatario',
			'nuevos_oficios.archivo_respuesta',
			'enviado',
			'finalizado',
			'masivo',

			DB::raw("COALESCE(
    CAST(nuevos_oficios.oficio_respuesta AS VARCHAR(50)),
    CASE 
      WHEN folios.total = 1 THEN CAST(folios.folio AS VARCHAR(50)) 
      ELSE folios.folios 
    END
) as oficio_respuesta"),
			DB::raw("COALESCE(t1.nombre_desti, 'Grupal') as nombre_desti"),
			DB::raw("CONCAT(
                RIGHT('0'+cast(DAY(nuevos_oficios.created_at) as varchar(2)),2),
                ' de ', dbo.fn_GetMonthName (nuevos_oficios.created_at, 'Spanish'),
                ' de ', YEAR(nuevos_oficios.created_at),
                ' a las ', CONVERT(VARCHAR(5),nuevos_oficios.created_at,108)
            ) as f_ingreso"),
			DB::raw("RIGHT(nuevos_oficios.archivo_respuesta, 3) as extension"),
			DB::raw("COALESCE(respuesta, descripcion) as respuesta")
		)
			->join('cat_areas', 'cat_areas.id', 'nuevos_oficios.id_area')
			->leftJoin(DB::raw("(
    SELECT destinatarios_oficio.id_oficio,
           CASE 
             WHEN t1.total = 1 AND destinatarios_oficio.tipo_usuario = 1 THEN directorios.nombre
             WHEN t1.total = 1 AND destinatarios_oficio.tipo_usuario = 2 THEN cat_destinatarios_externos.nombre
             WHEN t1.total > 1 THEN 'Multi Destinatario'
             ELSE '' END AS nombre_desti
    FROM destinatarios_oficio 
    JOIN (SELECT MAX(id) as id, COUNT(id) as total 
          FROM destinatarios_oficio  
          GROUP BY id_oficio ) as t1 ON t1.id = destinatarios_oficio.id
    LEFT JOIN directorios ON directorios.id = destinatarios_oficio.id_usuario
    LEFT JOIN cat_destinatarios_externos ON cat_destinatarios_externos.id = destinatarios_oficio.id_usuario
) as t1"), 'nuevos_oficios.id', 't1.id_oficio')

			->leftJoin(DB::raw("(
    SELECT id_oficio,
           COUNT(folio) as total,
           STRING_AGG(folio, ', ') as folios,
           MAX(folio) as folio
    FROM destinatarios_oficio
    GROUP BY id_oficio
) as folios"), 'folios.id_oficio', 'nuevos_oficios.id')
			->where('finalizado', 1)
			->orderBy('nuevos_oficios.created_at', 'desc')
			->get();

		$nuevos = $nuevos->map(function ($item) {
			if (!empty($item->oficio_respuesta)) {
				$folios = explode(',', $item->oficio_respuesta);
				$folios = array_map('trim', $folios);
				sort($folios, SORT_NUMERIC);

				$rango = [];
				$inicio = $prev = null;

				foreach ($folios as $folio) {
					if ($inicio === null) {
						$inicio = $prev = $folio;
						continue;
					}

					if ($folio == $prev + 1) {

						$prev = $folio;
					} else {

						if ($inicio == $prev) {
							$rango[] = $inicio;
						} else {
							$rango[] = "$inicio-$prev";
						}
						$inicio = $prev = $folio;
					}
				}


				if ($inicio !== null) {
					if ($inicio == $prev) {
						$rango[] = $inicio;
					} else {
						$rango[] = "$inicio-$prev";
					}
				}

				if (count($folios) <= 5) {
					$item->oficio_respuesta = implode(', ', $folios);
				} else {
					// Si son muchos, aplicar los rangos
					$item->oficio_respuesta = implode(', ', $rango);
				}
			}

			return $item;
		});
		return Inertia::render('Oficios/OficiosRespuestas', [
			'status' => session('status'),
			'oficios' => $oficios,
			'nuevos' => $nuevos
		]);
	}

	public function asignaResp(Request $request)
	{
		$request->validate([
			'proceso_impacta' => 'required',
			'usuario' => 'required',
		]);

		$oficio = Oficio::find($request->id);

		if ($oficio->proceso_impacta != $request->proceso_impacta) {
			$valor = "Aqui pondre el codigo para la bitacora";
		}

		$oficio->proceso_impacta = $request->proceso_impacta;
		$oficio->id_usuario = $request->usuario;
		$oficio->descripcion_rechazo = null;
		$oficio->save();

		$usuario = infoUsuario($request->usuario);

		iBitacoraOficio($request->id, 'Asignación de responsable', 'Se le asigno el oficio al colaborador: ' . $usuario->name, 'ion-man', 'primary');


		$folio = $oficio->ingreso == 'Email' ? $oficio->num_folio : $oficio->num_oficio;
		Mail::to($usuario->email)->later(now()->addSeconds(1), new Nuevo($usuario->name, $folio, $oficio->archivo));


		return back()->with('status', "Se asigno un responsable de forma correcta.");
	}


	public function respOficio($id)
	{
		$oficio = Oficio::find($id);

		$jefe = User::where('rol', 3)->where('id_area', $oficio->area)->first();
		$folio = $oficio->ingreso == 'Email' ? $oficio->num_folio : $oficio->num_oficio;

		if (\Auth::user()->rol == 3) {
			$oficio->finalizado = 1;
			$oficio->id_usuario = null;
			$asis = User::where('rol', 5)->first();
			IBitacoraOficio($id, 'Respuesta Jefe área', 'El jefe de área ' . $jefe->name . ' dio respuesta al oficio: ' . $oficio->num_oficio, 'fa fa-vcard', 'success');
			Mail::to($asis->email)->later(now()->addSeconds(2), new MailRespuestaOficio($asis->name, $folio, $id));
		} else if (\Auth::user()->rol == 4) {
			$usuario = infoUsuario($oficio->id_usuario);
			IBitacoraOficio($id, 'Respuesta Colaborador', 'El colaborador ' . $usuario->name . ' ha dado respuesta al oficio: ' . $oficio->num_oficio, 'fa fa-vcard', 'warning');
			Mail::to($jefe->email)->later(now()->addSeconds(2), new ColaboradorRespuesta($jefe->name, $usuario->name, $folio, $id));
		} else if (\Auth::user()->rol == 5) {
			$oficio->finalizado = 1;
			$oficio->enviado = 1;
			$oficio->fecha_respuesta = date('Y-m-d H:i:s');
			IBitacoraOficio($id, 'Respuesta enviada', 'Se ha enviado la respuesta del oficio', 'fa fa-send-o', 'primary');

			$usuario = infoUsuario($oficio->id_usuario);

			$mail = Mail::to($jefe->email);
			if (!empty($usuario)) {
				$mail->cc($usuario->email);
			}
			$mail->later(now()->addSeconds(2), new Enviado($folio));
		}
		$oficio->respuesta = 1;
		$oficio->descripcion_rechazo = null;
		$oficio->descripcion_rechazo_jefe = null;
		$oficio->descripcion_rechazo_final = null;
		$oficio->save();

		return to_route('misOficios');
	}

	public function rechazaOFicio(Request $request)
	{
		$request->validate([
			'descripcion' => 'required|min:2|max:500',
		]);


		$oficio = Oficio::find($request->id);
		$responsable = User::find($oficio->id_usuario);
		$oficio->descripcion_rechazo = $request->descripcion;
		$oficio->id_usuario = null;
		$oficio->save();

		iBitacoraOficio($request->id, 'Rechazo de oficio', 'Justificación: ' . $request->descripcion, 'fa fa-user-times', 'danger');

		$usuario = User::where('rol', 3)->where('id_area', $oficio->area)->first();

		$folio = $oficio->ingreso == 'Email' ? $oficio->num_folio : $oficio->num_oficio;
		Mail::to($usuario->email)->later(now()->addSeconds(1), new Rechazo($usuario->name, $folio, $responsable->name, $oficio->descripcion_rechazo));

		return back()->with('status', "Se rechazo el oficio.");
	}

	public function aceptResp($id)
	{
		$oficio = Oficio::find($id);
		$folio = $oficio->ingreso == 'Email' ? $oficio->num_folio : $oficio->num_oficio;
		$asis = User::where('rol', 5)->first();

		if (\Auth::user()->rol == 5) {
			$jefe = User::where('rol', 3)->where('id_area', $oficio->area)->first();

			$oficio->enviado = 1;
			$oficio->fecha_respuesta = date('Y-m-d H:i:s');
			IBitacoraOficio($id, 'Respuesta enviada', 'Se ha enviado la respuesta del oficio', 'fa fa-send-o', 'primary');

			$usuario = infoUsuario($oficio->id_usuario);


			$rutaPDF = $this->exportapdf($id, 1);
			$oficio->oficio_final = $rutaPDF;

			$mail = Mail::to($jefe->email);
			if (!empty($usuario)) {
				$mail->cc($usuario->email);
			}
			$mail->later(now()->addSeconds(2), new Enviado($folio));
		} else {
			$usuario = infoUsuario($oficio->id_usuario);
			$oficio->finalizado = 1;
			iBitacoraOficio($id, 'Se autorizo la respuesta', 'El jefe de área autorizo la respuesta del colaborador', 'fa fa-thumbs-o-up', 'success');
			Mail::to($usuario->email)->later(now()->addSeconds(1), new AceptaRespuestaJefe($usuario->name, $folio));
			Mail::to($asis->email)->later(now()->addSeconds(2), new MailRespuestaOficio($asis->name, $folio, $id));
		}
		$oficio->save();

		if (\Auth::user()->rol == 3) {
			return to_route('misOficios');
		} else {
			return to_route('oficiosRespuestas');
		}
	}

	/*
		Maneja el rechazo de una respuesta para un "Oficio".
		
		Valida la solicitud entrante para asegurar que el campo 'descripcion' esté presente y dentro de la longitud requerida.
		Busca el Oficio especificado por su ID, actualiza la descripción de rechazo, limpia cualquier descripción de respuesta previa
		y el archivo adjunto de respuesta, luego guarda los cambios.
		Finalmente, redirige al usuario a la ruta 'misOficios'.
		
		@param  \Illuminate\Http\Request  $request  La solicitud HTTP que contiene el ID del Oficio y la descripción del rechazo.
		@return \Illuminate\Http\RedirectResponse   Redirige a la ruta 'misOficios' después de procesar.
	*/
	public function rechazarResp(Request $request)
	{
		$request->validate([
			'descripcion' => 'required|min:2|max:500',
		]);

		$oficio = Oficio::find($request->id);
		$folio = $oficio->ingreso == 'Email' ? $oficio->num_folio : $oficio->num_oficio;
		$usuario = infoUsuario($oficio->id_usuario);

		if (\Auth::user()->rol == 3) {
			$oficio->descripcion_rechazo_jefe = $request->descripcion;
			iBitacoraOficio($request->id, 'Rechazo del jefe de área', 'Justificación: ' . $request->descripcion, 'fa fa-thumbs-o-down', 'danger');
			Mail::to($usuario->email)->later(now()->addSeconds(1), new RechazoRespuestaJefe($folio, $request->descripcion, $usuario->name, $request->id));
		} else {
			$oficio->descripcion_rechazo_final = $request->descripcion;
			$oficio->descripcion_rechazo_jefe = null;
			$oficio->finalizado = null;

			iBitacoraOficio($request->id, 'Rechazo de respuesta', 'Por parte de recepción documental con la siguiente justificación: ' . $request->descripcion, 'fa fa-thumbs-o-down', 'danger');

			$jefe = User::where('rol', 3)->where('id_area', $oficio->area)->first();
			Mail::to($jefe->email)->later(now()->addSeconds(1), new RechazoRespuestaFinal($jefe->name, $folio, $request->descripcion, $request->id, 'jefe', $oficio->id_usuario));

			if (!empty($usuario)) {
				Mail::to($usuario->email)->later(now()->addSeconds(3), new RechazoRespuestaFinal($usuario->name, $folio, $request->descripcion, $request->id, 'colaborador', $oficio->id_usuario));
			}
		}

		$oficio->respuesta = 0;
		$oficio->save();

		if (\Auth::user()->rol == 3) {
			return to_route('misOficios');
		} else {
			return to_route('oficiosRespuestas');
		}
	}

	public function saveCopias(Request $request)
	{
		$request->validate([
			'destinatario' => 'required',
			'dirigido_a' => 'required_if:destinatario,Interno',
		]);

		$copia = new Copia();
		if ($request->destinatario == 'Interno') {
			$direc = Directorio::find(intval($request->dirigido_a));
		} else {
			$direc = DestinatarioExterno::find(intval($request->dirigido_a));
		}

		if ($request->tipo == 1) {
			$copia->id_oficio = $request->id;
		} else {
			$copia->id_nuevo_oficio = $request->id;
		}
		$copia->nombre = $direc->nombre;
		$copia->cargo = $direc->cargo;
		$copia->dependencia = $direc->dependencia;
		$copia->id_directorio = $request->dirigido_a;
		$copia->save();

		$copy = Copia::select('id', 'id_oficio', 'nombre', 'cargo', 'dependencia')->where('id_oficio', $request->id)->get();

		return back()->with('copy', $copy);
	}

	public function deleteCopias($id)
	{
		$copia = Copia::find($id);
		if ($copia) {
			$copia->delete();
			return back()->with('status', "Se elimino la copia del oficio.");
		} else {
			return back()->with('error', "No se encontro la copia del oficio.");
		}
	}

	public function saveResp(Request $request)
	{
		$request->validate([
			'destinatarioDos' => 'required',
			'dirigido_aDos' => 'required_if:destinatarioDos,Interno',
			'nombreDos' => 'required|min:2|max:155',
			'cargoDos' => 'nullable|min:2|max:255',
			'dependenciaDos' => 'nullable|min:2|max:255',
			'asunto' => 'nullable|min:2',
			'comentario' => 'nullable|min:2|max:1000'
		]);


		$bandera = RespuestaOficio::where('id_oficio', $request->id_oficio)->first();
		if (empty($bandera)) {
			$respuesta = new RespuestaOficio();

			$ultimo = Variables::where('variable', 'Oficio')->first();
			$oficio = ($ultimo->valor + 1);
			Variables::where('variable', 'Oficio')->update(['valor' => $oficio]);

			$respuesta->id_oficio = $request->id_oficio;
			$respuesta->oficio_respuesta = $oficio;
		} else {
			$respuesta = RespuestaOficio::find($bandera->id);
		}

		$respuesta->id_directorio = $request->dirigido_aDos;
		$respuesta->tipo_destinatario = $request->destinatarioDos;
		$respuesta->nombre = $request->nombreDos;
		$respuesta->cargo = $request->cargoDos;
		$respuesta->dependencia = $request->dependenciaDos;
		$respuesta->respuesta = $request->asunto;
		$respuesta->comentario = $request->comentario;
		$respuesta->save();

		return back()->with('status', "Se guardo la respuesta del oficio.");
	}

	public function detailResp($id)
	{
		try {
			$copy = Copia::select('id', 'id_oficio', 'id_directorio', 'nombre', 'cargo', 'dependencia')->where('id_oficio', $id)->get();

			$respuesta = RespuestaOficio::select('id_oficio', 'tipo_destinatario as destinatarioDos', 'nombre as nombreDos', 'cargo as cargoDos', 'dependencia as dependenciaDos', 'id_directorio as dirigido_aDos', 'respuesta as asunto')
				->where('id_oficio', $id)
				->first();

			$msg = [
				'code' => 200,
				'mensaje' => 'Detalle de la respuesta',
				'copy' => $copy,
				'respuesta' => $respuesta
			];
		} catch (\Illuminate\DataBase\QueryException $ex) {
			$msg = [
				'code' => 400,
				'mensaje' => 'Intente de nuevo o consulte al administrador del sistema',
				'data' => $ex
			];
		} catch (Exception $e) {
			$msg = [
				'code' => 400,
				'mensaje' => 'Intente de nuevo o consulte al administrador del sistema',
				'data' => $e
			];
		}

		return response()->json($msg, $msg['code']);
	}

	/**
	 * Exporta un oficio en formato PDF.
	 *
	 * Esta función genera un archivo PDF a partir de la información de un oficio, incluyendo la respuesta,
	 * las copias y los datos del área y proceso relacionados. La fecha actual se formatea en español.
	 *
	 * @param int $opcion Indica si el PDF se descarga (mayor a 0) o se muestra en el navegador (0 o menor).
	 * @param int $id ID del oficio a exportar.
	 * @return \Illuminate\Http\Response Respuesta HTTP con el PDF generado para visualización.
	 */
	public function exportapdf($id, $guarda = 0)
	{
		$respuesta = RespuestaOficio::where('id_oficio', $id)->first();


		if ($respuesta?->fecha_respuesta) {
			$fecha = $respuesta?->fecha_respuesta;
		} else {
			$fecha = date('Y-m-d');
		}

		// Obtener la fecha actual en español, ejemplo: 04 de julio de 2025
		setlocale(LC_TIME, 'es_ES.UTF-8', 'Spanish_Spain.1252');
		$fechaEscrita = strftime('%d de %B de %Y', strtotime($fecha));
		// En caso de que strftime no funcione correctamente en Windows, usar una alternativa:
		if (strpos($fechaEscrita, '%') !== false) {
			$meses = [
				1 => 'enero',
				2 => 'febrero',
				3 => 'marzo',
				4 => 'abril',
				5 => 'mayo',
				6 => 'junio',
				7 => 'julio',
				8 => 'agosto',
				9 => 'septiembre',
				10 => 'octubre',
				11 => 'noviembre',
				12 => 'diciembre'
			];
			$dia = date('d');
			$mes = $meses[intval(date('m'))];
			$anio = date('Y');
			$fechaEscrita = "$dia de $mes de $anio";
		}

		$copias = Copia::where('id_oficio', $id)->get();

		$oficio = Oficio::select(
			'users.iniciales as area',
			'u.iniciales as proceso',
			'cat_areas.siglas'
		)
			->leftJoin('users', function ($join) {
				$join->on('users.rol', '=', DB::raw("3"));
				$join->on('users.id_area', '=', 'oficios.area');
			})
			->leftJoin('users as u', function ($join) {
				$join->on('u.rol', '=', DB::raw("4"));
				$join->on('u.id', '=', 'oficios.id_usuario');
			})
			->join('cat_areas', 'cat_areas.id', 'oficios.area')
			->where('oficios.id', $id)
			->first();

		$pdf = Pdf::loadView('Oficios.Vice', [
			'respuesta' => $respuesta,
			'copias' => $copias,
			'oficio' => $oficio,
			'fechaEscrita' => $fechaEscrita,
			'tipo_usuario' => 1, //provisional
		]);

		if ($guarda == 1) {
			$rutaArchivo = 'pdfs_oficios/oficio_' . $id . '_' . now()->format('YmdHis') . '.pdf';
			$pdfContent = $pdf->output();
			\Storage::disk('files')->put($rutaArchivo, $pdfContent);

			return $rutaArchivo;
		}

		return $pdf->stream('respuesta_oficio.pdf');
	}

	public function uploadFiles(Request $request, $id)
	{
		ini_set('upload_max_filesize', '30M');
		ini_set('post_max_size', '30M');

		if (!$request->expectsJson()) {
			$request->headers->set('Accept', 'application/json');
		}

		$request->validate([
			'file' => 'required|file|max:25600|mimes:pdf,doc,docx,jpg,png,xlsx,xls,csv,txt, pptx, xml, zip, rar',
		]);

		if ($request->hasFile('file')) {
			$file = $request->file('file');

			$archivo = 'adjuntos_oficios/' . time() . "_" . \Auth::user()->id . "_" . $request->file->getClientOriginalName();
			$path = \Storage::disk('files')->put($archivo, \File::get($request->file));

			$archivoOficio = new ArchivoOficio();
			$archivoOficio->id_oficio = $id;
			$archivoOficio->archivo = $archivo;
			$archivoOficio->nombre = $request->file->getClientOriginalName();
			$archivoOficio->save();

			return response()->json([
				'id' => $archivoOficio->id,
				'path' => $path,
				'url' => \Storage::url($path),
			]);
		}

		return response()->json(['error' => 'No se subió ningún archivo'], 400);
	}

	public function deleteFile(Request $request)
	{
		$idArchivo = $request->getContent();

		if (!$idArchivo) {
			return response()->json(['error' => 'Archivo no especificado'], 400);
		}

		$archivo = ArchivoOficio::find($idArchivo);

		if (!$archivo) {
			return response()->json(['error' => 'Archivo no encontrado'], 400);
		}



		if (\Storage::disk('files')->exists($archivo->archivo)) {
			\Storage::disk('files')->delete($archivo->archivo);
			ArchivoOficio::where('id', $archivo->id)->delete();
			return response()->json(['success' => true]);
		}

		return response()->json(['error' => 'Archivo no encontrado'], 404);
	}

	public function downloadFiles($id)
	{
		$archivos = ArchivoOficio::where('id_oficio', $id)->get();

		if ($archivos->isEmpty()) {
			return redirect()->route('dashboard')->withErrors(['error' => 'No hay archivos para descargar']);
		}

		$zip = new \ZipArchive();
		$zipFileName = 'archivos_oficio_' . $id . '.zip';
		$tmpFile = tempnam(sys_get_temp_dir(), $zipFileName);

		if ($zip->open($tmpFile, \ZipArchive::CREATE) !== true) {
			return redirect()->route('dashboard')->withErrors(['error' => 'No se pudo crear el archivo comprimido.']);
		}

		foreach ($archivos as $archivo) {
			$path = \Storage::disk('files')->path($archivo->archivo);
			if (file_exists($path)) {
				$zip->addFile($path, $archivo->nombre);
			}
		}

		$zip->close();

		return response()->download($tmpFile, $zipFileName)->deleteFileAfterSend(true);
	}

	public function subeEvidenciaRecibido(Request $request)
	{
		ini_set('upload_max_filesize', '30M');
		ini_set('post_max_size', '30M');
		$request->validate([
			'archivo' => 'required|file|max:25600|mimes:pdf,jpg,png,jpeg',
		]);

		$archivo = 'recibido_oficios/' . time() . "_" . \Auth::user()->id . "_" . $request->archivo->getClientOriginalName();
		$path = \Storage::disk('files')->put($archivo, \File::get($request->archivo));

		if ($request->tipo == 'respuesta') {
			$oficio = Oficio::find($request->id);
		} else if ($request->tipo == 'nuevo') {
			$oficio = NuevoOficio::find($request->id);
		} else if ($request->tipo == 'destinatario') {
			$oficio = DestinatarioOficio::find($request->id);
		}
		$oficio->archivo_respuesta = $archivo;
		$oficio->save();

		if ($request->tipo == 'destinatario') {
			$bandera = DestinatarioOficio::select(
				'id_oficio',
				DB::raw("SUM( CASE WHEN archivo_respuesta IS NULL THEN 1 ELSE 0 END ) as count")
			)
				->where('id_oficio', $oficio->id_oficio)
				->groupBy('id_oficio')
				->first();

			if ($bandera->count == 0) {
				$nuevo = NuevoOficio::find($bandera->id_oficio);
				$nuevo->archivo_respuesta = "TERMINADO";
				$nuevo->save();

				return redirect()->route('oficiosRespuestas');
			}
		}

		return back()->with('status', "Se guardo la respuesta del oficio.");
	}


	public function actualizaFecha(Request $request)
	{
		$request->validate([
			'fecha' => 'required|date',
		]);

		$oficio = RespuestaOficio::find($request->id);
		$oficio->fecha_respuesta = $request->fecha;
		$oficio->save();

		return back()->with('status', "Se actualizo la fecha de respuesta del oficio.");
	}

	public function getArchivosAdjuntos($id, $tipo)
	{
		try {
			$archivos = ArchivoOficio::select('id', 'nombre', 'archivo')
				->where($tipo, $id)
				->get()
				->map(function ($archivo) {
					$extension = pathinfo($archivo->archivo, PATHINFO_EXTENSION);

					if ($extension == "pdf" || $extension == "jpg" || $extension == "jpeg" || $extension == "png") {
						$tipo = 1;
						$url = $archivo->archivo;
					} else {
						$url = asset("files/" . $archivo->archivo);
						$tipo = 2;
					}

					return [
						'id' => $archivo->id,
						'tipo' => $tipo,
						'url' => $url,
						'nombre' => $archivo->nombre,
						'extension' => $extension,
					];
				});

			$msg = [
				'code' => 200,
				'mensaje' => 'Listado de archivos adjuntos al oficio',
				'data' => $archivos
			];
		} catch (\Illuminate\DataBase\QueryException $ex) {
			$msg = [
				'code' => 400,
				'mensaje' => 'Intente de nuevo o consulte al administrador del sistema',
				'data' => $ex
			];
		} catch (Exception $e) {
			$msg = [
				'code' => 400,
				'mensaje' => 'Intente de nuevo o consulte al administrador del sistema',
				'data' => $e
			];
		}

		return response()->json($msg, $msg['code']);
	}

	public function getEstatus($valor, $tipo)
	{
		try {
			$where = "";
			if (\Auth::user()->rol == 3) {
				$where = " AND area = " . \Auth::user()->id_area;
				$whereDos = " AND id_area = " . \Auth::user()->id_area . " AND revision = 1";
			} else if (\Auth::user()->rol == 4) {
				$where = " AND id_usuario = " . \Auth::user()->id;
				$whereDos = " AND id_usuario = " . \Auth::user()->id;
			} else if (\Auth::user()->rol == 5) {
				$where = " AND finalizado = 1";
			}

			switch ($valor) {
				case 0:
					$where .= "";
					break;
				case 1:
					$where .= " AND CASE 
					WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, oficios.fecha_respuesta, 120) ) < cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NOT NULL THEN '#5fd710'
					WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, getdate(), 120) ) < cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NULL THEN '#f5f233'
					WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, getdate(), 120) ) > cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NULL THEN '#f98200'
					WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, oficios.fecha_respuesta, 120) ) > cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NOT NULL THEN '#ff2d2d'
					END = '#5fd710' ";
					break;
				case 2:
					$where .= " AND CASE 
					WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, oficios.fecha_respuesta, 120) ) < cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NOT NULL THEN '#5fd710'
					WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, getdate(), 120) ) < cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NULL THEN '#f5f233'
					WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, getdate(), 120) ) > cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NULL THEN '#f98200'
					WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, oficios.fecha_respuesta, 120) ) > cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NOT NULL THEN '#ff2d2d'
					END = '#f5f233' ";
					break;
				case 3:
					$where .= " AND CASE 
					WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, oficios.fecha_respuesta, 120) ) < cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NOT NULL THEN '#5fd710'
					WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, getdate(), 120) ) < cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NULL THEN '#f5f233'
					WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, getdate(), 120) ) > cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NULL THEN '#f98200'
					WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, oficios.fecha_respuesta, 120) ) > cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NOT NULL THEN '#ff2d2d'
					END = '#f98200' ";
					break;
				case 4:
					$where .= " AND CASE 
					WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, oficios.fecha_respuesta, 120) ) < cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NOT NULL THEN '#5fd710'
					WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, getdate(), 120) ) < cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NULL THEN '#f5f233'
					WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, getdate(), 120) ) > cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NULL THEN '#f98200'
					WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, oficios.fecha_respuesta, 120) ) > cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NOT NULL THEN '#ff2d2d'
					END = '#ff2d2d' ";
					break;
			}


			$oficios = Oficio::select(
				DB::raw("CASE 
        WHEN DATEDIFF (MINUTE, convert(varchar, oficios.created_at, 120), convert(varchar, oficios.fecha_respuesta, 120)) < cat_areas.minutos_oficio 
             AND oficios.fecha_respuesta IS NOT NULL THEN 1 -- Verde
        WHEN DATEDIFF (MINUTE, convert(varchar, oficios.created_at, 120), convert(varchar, getdate(), 120)) < cat_areas.minutos_oficio 
             AND oficios.fecha_respuesta IS NULL THEN 2 -- Amarillo
        WHEN DATEDIFF (MINUTE, convert(varchar, oficios.created_at, 120), convert(varchar, getdate(), 120)) > cat_areas.minutos_oficio 
             AND oficios.fecha_respuesta IS NULL THEN 3 -- Naranja
        WHEN DATEDIFF (MINUTE, convert(varchar, oficios.created_at, 120), convert(varchar, oficios.fecha_respuesta, 120)) > cat_areas.minutos_oficio 
             AND oficios.fecha_respuesta IS NOT NULL THEN 4 -- Rojo
        ELSE 5 END as estatus_valor"),
				DB::raw("CASE 
				WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, oficios.fecha_respuesta, 120) ) < cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NOT NULL THEN '#5fd710'
				WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, getdate(), 120) ) < cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NULL THEN '#f5f233'
				WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, getdate(), 120) ) > cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NULL THEN '#f98200'
				WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, oficios.fecha_respuesta, 120) ) > cat_areas.minutos_oficio AND oficios.fecha_respuesta IS NOT NULL THEN '#ff2d2d'
				END as color"),
				DB::raw("CONCAT( RIGHT('0'+cast(DAY(oficios.created_at) as varchar(2)),2) ,' de ', dbo.fn_GetMonthName (oficios.created_at, 'Spanish'),' de ',YEAR(oficios.created_at),' a las ', CONVERT(VARCHAR(5),oficios.created_at,108)) as f_ingreso"),
				'oficios.id',
				'num_folio',
				'num_oficio',
				DB::raw(" CASE WHEN ingreso = 'Email' THEN num_folio ELSE num_oficio END as numero_oficio "),
				'cat_des.nombre as des',
				'cat_areas.nombre as area',
				'cat_procesos.nombre as proceso',
				'dep_ua',
				'area as id_area',
				DB::raw("coalesce(users.name,'' ) as responsable"),
				'oficios.id_usuario',
				'proceso_impacta',
				'descripcion',
				'oficios.archivo',
				'oficios.descripcion_respuesta',
				'oficios.archivo_respuesta',
				'oficios.descripcion_rechazo',
				'oficios.finalizado',
				'oficios.respuesta',
				'oficios.oficio_final',
				'oficios.descripcion_rechazo_jefe',
				'descripcion_rechazo_final',
				DB::raw("RIGHT(oficios.archivo_respuesta, 3) as extension"),
				'respuestas_oficio.nombre as destinatario',
				't3.total_inicial',
				't4.total_respuesta',
				'respuestas_oficio.respuesta as asunto',
			)
				->join('cat_des', 'cat_des.id', 'oficios.dep_ua')
				->join('cat_areas', 'cat_areas.id', 'oficios.area')
				->leftJoin('cat_procesos', 'cat_procesos.id', 'oficios.proceso_impacta')
				->Leftjoin('users', 'users.id', 'oficios.id_usuario')
				->leftJoin('respuestas_oficio', 'respuestas_oficio.id_oficio', 'oficios.id')
				->leftJoin(DB::raw("(SELECT COUNT(id) as total_inicial, id_oficio_inicial FROM archivos_oficios WHERE id_nuevo_oficio IS NULL AND id_oficio IS NULL GROUP BY id_oficio_inicial ) as t3"), 't3.id_oficio_inicial', 'oficios.id')
				->leftJoin(DB::raw("(SELECT COUNT(id) as total_respuesta, id_oficio  FROM archivos_oficios WHERE id_nuevo_oficio IS  NULL AND id_oficio_inicial IS NULL GROUP BY id_oficio ) as t4"), 't4.id_oficio', 'oficios.id')
				->whereRaw("1=1 $where")
				->orWhere("area", 1)
				->orderBy('oficios.created_at', 'desc')

				->get();


			$msg = [
				'code' => 200,
				'mensaje' => 'Listado de procesos',
				'data' => $oficios
			];
		} catch (\Illuminate\DataBase\QueryException $ex) {
			$msg = [
				'code' => 400,
				'mensaje' => 'Intente de nuevo o consulte al administrador del sistema',
				'data' => $ex
			];
		} catch (Exception $e) {
			$msg = [
				'code' => 400,
				'mensaje' => 'Intente de nuevo o consulte al administrador del sistema',
				'data' => $e
			];
		}

		return response()->json($msg, $msg['code']);
	}
}
