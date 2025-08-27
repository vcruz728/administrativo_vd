<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Oficio PDF</title>
    <style>
        @page {
            size: letter;
            margin-top: 4.5cm;
            margin-left: 4.5cm;
            margin-right: 2.5cm;
            margin-bottom: 2.5cm;


            
        }

        @font-face {
            font-family: 'SourceSansPro';
            src: url('{{ public_path('fonts/SourceSansPro-Regular.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'SourceSansPro';
            src: url('{{ public_path('fonts/SourceSansPro-Bold.ttf') }}') format('truetype');
            font-weight: 700;
            font-style: normal;
        }

        @font-face {
            font-family: 'SourceSansPro';
            src: url('{{ public_path('fonts/SourceSansPro-Bold.ttf') }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        
        @font-face {
            font-family: 'SourceSansPro';
            src: url('{{ public_path('fonts/SourceSansPro-Italic.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: italic;
        }
        


        @font-face {
            font-family: 'SourceSansPro';
            src: url('{{ public_path('fonts/SourceSansPro-BoldItalic.ttf') }}') format('truetype');
            font-weight: 700;
            font-style: italic;
        }

        @font-face {
            font-family: 'SourceSansPro';
            src: url('{{ public_path('fonts/SourceSansPro-BoldItalic.ttf') }}') format('truetype');
            font-weight: bold;
            font-style: italic;
        }


        body {
            font-family: 'SourceSansPro', arial !important;
            font-size: 9pt !important;
            position: relative;
            
        }
        header {
            position: fixed;
            top: -145px;
            left: -30px;
            right: 0;
            width: 100%;
            text-align: center;
        }
        footer {
            position: fixed;
            bottom: -125px;
            left: 0;
            right: 0;
            height: 100px;
            text-align: center;
            font-size: 12px;
        }
        .background {
            position: fixed;
            right: -343px;
            top: 65px;
            width: 670px;
            z-index: -1;
        }
        .content {
            position: relative;
            z-index: 1;
        }
        .pagenum:before {
            content: counter(page);
        }
        
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        
        thead {
            display: table-header-group;
        }
        
        tfoot {
            display: table-footer-group;
        }
        
        
        p {
            margin: 0 !important;
            padding: 0 !important;

            font-size: 9pt !important;
        }
        
        table {
            page-break-inside: auto;
            width: 100%;
            border-spacing: 0 !important;
            border-collapse: collapse !important;
        }

        th, td {
            border: 1px solid #000 !important;
            padding: 0 !important;
            padding-left: 6px !important;
        }

        .sello-movil {
            position: absolute;
            top: 45px;
            left: 120px;
            width: 19%;
            z-index: 100000;
            pointer-events: none; /* Opcional: para que no bloquee el texto */
            
        }

        .firma-movil {
            position: absolute;
            top: 53px;
            left: 0px;
            width: 30%;
            z-index: 100000;
            pointer-events: none; /* Opcional: para que no bloquee el texto */
            
        }

        table {
        width: 100% !important;
        table-layout: fixed !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
    }

        .contenido-dinamico {
            font-family: 'SourceSansPro', arial, sans-serif;
            font-size: 9pt;
            color: black;
        }

        .contenido-dinamico * {
            font-family: inherit;
            font-size: inherit;
            color: inherit;
        }

        .dependencia {
            line-height: 0.9 !important;
            font-family: 'SourceSansPro';
            font-weight: bold;
        }


    </style>
</head>
<body>
    <!-- <img src="{{ public_path('img/minerva_gris.png') }}" class="background" />  descomentar cuando no haya hojas membretadas -->
    <header>
        <!-- <img src="{{ public_path('img/minver_buap_azul.png') }}" width="100" >  descomentar cuando no haya hojas membretadas -->  
    </header>
    <footer>
    <div style="width: 100%; text-align: center; font-size: 10px; color: rgb(136, 136, 136); padding: 4px 0 !important; background: rgba(255,255,255,0.8); display:none;"> <!-- display:none; quitar cuando no haya hojas membretadas -->
        <table style="margin: 0 auto;  width: auto;">
            <tr>
                <td style="text-align: right; width: 90px;  vertical-align: top; padding-right: 0 !important; border: none !important; line-height: 0.8 !important;">
                    Vicerrectoria<br>de Docencia
                </td>
                <td style=" vertical-align: middle; text-align: center; border: none !important;">
                    <div style="display: inline-block; width: 1.5px; height: 45px; background: #adcbd3; margin: 0 auto;"></div>
                </td>
                <td style="text-align: left;  vertical-align: top; padding-left: 7px !important; border: none !important; line-height: 0.8 !important;">
                    4to. piso de la Torre de Gestión<br>
                    Académica y servicios Administrativos,<br>
                    Ciudad Universitaría, Puebla, Pue.<br>
                    Tel. 222 229 55 00, Ext. 3553 y 5900
                </td>
            </tr>
        </table>
    </div>
</footer>
    <div class="content">
    <div><p style="font-family: 'SourceSansPro'; font-style: italic; ">Oficio No. {{ $oficio?->siglas }}/{{ $respuesta?->oficio_respuesta }}/{{ date('Y') }}</p></div>
     <br>
       <div style="max-width: 60%;">
     @switch($tipo_usuario)
            @case(1)
                <p class="dependencia">{{ $respuesta?->nombre }}</p>
                <p class="dependencia">{{ $respuesta?->cargo }} {{ $respuesta?->dependencia }} de la </p>
                <p class="dependencia">Benemérita Universidad Autónoma de Puebla</p>
                @break

            @case(2)
                    <p class="dependencia">{{ $respuesta?->nombre }}</p>
                    <p class="dependencia">{{ $respuesta?->cargo }} {{ $respuesta?->dependencia }}</p>

                @break
        @endswitch

        </div>


            <p style="font-family: 'SourceSansPro'; font-weight: bold;">PRESENTE</p>

        </div>
    <br><br>
    <!--  contenido -->
<div class="contenido-dinamico">
    {!! $respuesta?->respuesta ?? '' !!}
</div>
    <!--  termina contenido -->
      <br>

      
      <p style="font-family: 'SourceSansPro'; font-style: italic;">De antemano agradezco su atención y le reitero la seguridad de mi más distinguida consideración.</p>


        <br>
        <br>
        <div style="position: relative; page-break-inside: avoid;">
            <p style="font-family: 'SourceSansPro'; font-style: italic;">Atentamente</p>
            <p style="font-family: 'SourceSansPro'; font-style: italic;">“Pensar bien, para vivir mejor”</p>
            <p style="font-family: 'SourceSansPro'; font-style: italic;">H. Puebla de Zaragoza a {{ $fechaEscrita }}</p>
            
            <br>
            <br>

            
            <p style="font-family: 'SourceSansPro'; font-style: italic;">Dr. José Jaime Vázquez López</p>
            <p style="font-family: 'SourceSansPro'; font-style: italic;">Vicerrector de Docencia</p>
            <!-- Descomentart si ya quieren sello y firma
            <img src="{{ public_path('img/sello.png') }}" class="sello-movil" />
            <img src="{{ public_path('img/firma.png') }}" class="firma-movil" />
            -->
        </div>
    


    <div  style="margin-top: 20px;">
        @foreach($copias as $value)
            <p style="font-family: 'SourceSansPro'; font-style: italic;  font-size: 10px !important;">
                C.c.p. {{ $value->nombre }}@if($value->cargo) , {{$value->cargo}} @endif @if($value->dependencia) {{ str_replace([' de la', ' de el', ' de los', ' de las', ' de'], '', $value->dependencia) }}@endif p.s.c.
            </p>
        @endforeach
        <p style="font-family: 'SourceSansPro';  font-style: italic; margin: 0px; font-size: 10px !important;">C.c.p. Archivo</p>
        <p style="font-family: 'SourceSansPro';  font-style: italic; margin: 0px; font-size: 10px !important;">Dr.JJVL/{{ $oficio?->area }}@if($oficio?->proceso)/{{ $oficio?->proceso }} @endif</p>
    </div>
   
</body>
</html>