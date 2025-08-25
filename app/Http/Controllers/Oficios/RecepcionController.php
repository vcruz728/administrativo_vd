<?php

namespace App\Http\Controllers\Oficios;
use Inertia\Inertia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Oficios\Oficio;
use App\Models\Oficios\ArchivoOficio;
use App\Models\Catalogos\Des;
use App\Models\Catalogos\Areas;
use App\Models\Catalogos\Procesos;
use Illuminate\Support\Facades\Mail;
use App\Mail\Oficios\Nuevo;
use App\Models\User;
use DB;
use App\Models\Oficios\Nuevo as NuevoOficio;

class RecepcionController extends Controller
{
    public function index()
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
			WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, oficios.fecha_respuesta, 120) ) < 4320 AND oficios.fecha_respuesta IS NOT NULL THEN '#5fd710'
			WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, getdate(), 120) ) < 4320 AND oficios.fecha_respuesta IS NULL THEN '#f5f233'
			WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, getdate(), 120) ) > 4320 AND oficios.fecha_respuesta IS NULL THEN '#f98200'
			WHEN DATEDIFF ( MINUTE, convert(varchar, oficios.created_at, 120)  , convert(varchar, oficios.fecha_respuesta, 120) ) > 4320 AND oficios.fecha_respuesta IS NOT NULL THEN '#ff2d2d'
			ELSE '#000000' END as color"),
			DB::raw("CONCAT( RIGHT('0'+cast(DAY(oficios.created_at) as varchar(2)),2) ,' de ', dbo.fn_GetMonthName (oficios.created_at, 'Spanish'),' de ',YEAR(oficios.created_at),' a las ', CONVERT(VARCHAR(5),oficios.created_at,108)) as f_ingreso"),
    		'oficios.id',
    		'ingreso',
    		'num_folio',
    		'num_oficio',
    		DB::raw(" CASE WHEN ingreso = 'Email' THEN num_folio ELSE num_oficio END as numero_oficio "),
    		'cat_des.nombre as des',
    		'cat_areas.nombre as area',
    		'cat_procesos.nombre as proceso',
    		'dep_ua',
			'area as id_area',
			'proceso_impacta',
			'descripcion',
			'oficios.archivo',
			'oficios.archivo_respuesta',
			'oficios.oficio_final',
			DB::raw("RIGHT(oficios.archivo_respuesta, 3) as extension"),
			'respuestas_oficio.respuesta as asunto',

    	)
    	->join('cat_des','cat_des.id','oficios.dep_ua')
    	->join('cat_areas','cat_areas.id','oficios.area')
		->leftJoin('respuestas_oficio','respuestas_oficio.id_oficio','oficios.id')
    	->join('cat_procesos','cat_procesos.id','oficios.proceso_impacta')
    	->orderBy('oficios.created_at','desc')
    	->get();

	$nuevos = NuevoOficio::select(
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
    DB::Raw("COALESCE(respuesta, descripcion) as respuesta")
)
->join('cat_areas','cat_areas.id','nuevos_oficios.id_area')
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
) as t1"),'nuevos_oficios.id','t1.id_oficio')

->leftJoin(DB::raw("(
    SELECT id_oficio,
           COUNT(folio) as total,
           STRING_AGG(CAST(folio AS VARCHAR(50)), ', ') as folios,
           MAX(folio) as folio
    FROM destinatarios_oficio
    GROUP BY id_oficio
) as folios"),'folios.id_oficio','nuevos_oficios.id')
->whereNotNull('nuevos_oficios.archivo_respuesta')
->orderBy('nuevos_oficios.created_at', 'desc')
->get();

$nuevos = $nuevos->map(function($item) {
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
    	return Inertia::render('Oficios/Recepcion', [
            'status' => session('status'),
            'oficios' => $oficios,
            'nuevoHistorico' => $nuevos
        ]);
    }

    public function altaOficio($id = 0)
	{
    	$oficio = Oficio::select(
    		'oficios.id',
    		'ingreso',
    		'num_folio',
    		'num_oficio',
    		'cat_des.nombre as des',
    		'cat_areas.nombre as area',
    		'cat_procesos.nombre as proceso',
    		'dep_ua',
			'area as id_area',
			'proceso_impacta',
			'descripcion',
			'archivo'

    	)
    	->join('cat_des','cat_des.id','oficios.dep_ua')
    	->join('cat_areas','cat_areas.id','oficios.area')
    	->join('cat_procesos','cat_procesos.id','oficios.proceso_impacta')
    	->where('oficios.id', $id)
    	->first();

    	$procesos = Procesos::getSelForArea($id);

		$archivos = ArchivoOficio::where('id_oficio_inicial', $id)
		->get()
		->map(function($archivo) {
			$extension = pathinfo($archivo->archivo, PATHINFO_EXTENSION);
			
			if($extension == "pdf" || $extension == "jpg" || $extension == "jpeg" || $extension == "png"){
				$url = $archivo->archivo;
			}else{
				$url = asset("files/".$archivo->archivo);
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

    	return Inertia::render('Oficios/FormOficios', [
            'status' => session('status'),
            'des' => Des::getSel(),
            'areas' => Areas::getSel(),
            'oficioInicial' => $oficio,
            'procesos' => $procesos,
			'files' => $archivos
        ]);
    }

    public function save(Request $request)
	{
		
		if(isset($request->id)){
			$ofi = Oficio::find($request->id);
			$regla = 'nullable';
			$evidencia = $ofi->archivo;
		}else{
    		$ofi = new Oficio();
    		$regla = 'required';
		}

    	$request->validate([
    		'ingreso' => 'required',
			'num_oficio' => 'required_if:ingreso,Físico',
			'num_folio' => 'required_if:ingreso,Email',
			'dep_ua' => 'required',
			'area' => 'required',
			'proceso_impacta' => ($request->area == 1 ? 'nullable' : 'required'),
			'archivo' => $regla.'|mimes:pdf|max:4096',
			'descripcion' => 'required|min:2|max:1000',
        ]);

		if($request->file('archivo')){
			if($ofi->archivo != ''){
				\Storage::disk('files')->delete($ofi->archivo);
			}

	    	$evidencia = 'oficios/'.time()."_".\Auth::user()->id."_".$request->archivo->getClientOriginalName();
			$path = \Storage::disk('files')->put($evidencia, \File::get($request->archivo));
		}

    	$ofi->ingreso = $request->ingreso;
	    $ofi->num_oficio = $request->num_oficio;
	    $ofi->num_folio = $request->num_folio;
	    $ofi->dep_ua = $request->dep_ua;
	    $ofi->area = $request->area;
	    $ofi->proceso_impacta = $request->proceso_impacta;
	    $ofi->descripcion = $request->descripcion;
		$ofi->archivo = $evidencia;
		$ofi->save();

        $folio = $request->ingreso == 'Email' ? $request->num_folio : $request->num_oficio;

        
		if($request->area != "1"){
			$datosOfi = Oficio::select('cat_procesos.nombre as proceso', 'cat_areas.nombre as area', 'cat_des.nombre as des', 'descripcion', 'ingreso')->join('cat_des','cat_des.id','oficios.dep_ua')->join('cat_procesos','cat_procesos.id','oficios.proceso_impacta')->join('cat_areas','cat_areas.id', 'oficios.area')->where('oficios.id', $ofi->id)->first();
			$descriponI = "<ul><li><strong>Ingreso de la solicitud</strong>: ".$datosOfi->ingreso."</li><li><strong>Dependencia o Unidad Académica</strong>: ".$datosOfi->des."</li><li><strong>Área responsable</strong>: ".$datosOfi->area."</li><li><strong>Proceso al que impacta</strong>: ".$datosOfi->proceso."</li></ul>";
			
			if(!isset($request->id)){
				$usuario = User::where('rol', 3)->where('id_area', $request->area)->first();
				iBitacoraOficio($ofi->id,"Recepción de oficio: $folio", $descriponI, "fa fa-file-o", "success");

				if(!empty($usuario)){
					Mail::to($usuario->email)->later(now()->addSeconds(2), new Nuevo($usuario->name, $folio, $evidencia));
				}
			}else{  
				iBitacoraOficio($ofi->id,"Edición oficio: $folio", $descriponI, "fe fe-edit", "warning");
			}
		}

		return redirect()->route('oficios.modificaOficio',['id' => $ofi->id])->with('status', "Oficio guardado correctamente");

		//return back()->with('status', "Oficio guardado correctamente");
    }

	public function uploadFiles(Request $request, $id){
		if (!$request->expectsJson()) {
			$request->headers->set('Accept', 'application/json');
		}
	
		$request->validate([
            'file' => 'required|file|max:25600|mimes:pdf,doc,docx,jpg,png,xlsx,xls,csv,txt, pptx,xml,zip,rar',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');

			$archivo = 'adjuntos_oficios/'.time()."_".\Auth::user()->id."_".$request->file->getClientOriginalName();
    	    $path = \Storage::disk('files')->put($archivo, \File::get($request->file));

			$archivoOficio = new ArchivoOficio();
			$archivoOficio->id_oficio_inicial = $id;
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

	public function deleteFile(Request $request){
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

}
