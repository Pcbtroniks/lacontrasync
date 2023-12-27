<?php

require_once 'lib/GetParams.php';
require_once 'lib/DumpData.php';
require_once 'lib/PrintData.php';

class GetFileData {

    private $odbc; // Conexión a la base de datos
    private $File; // Archivo a leer
    private $mrtienda_path; // Ruta de Mr. Tienda

    public function __construct($mrtienda_path) {
        // dd((new GetParams())->GetQueryParams('read'));
        # Conexión con a la base de datos
        $this->odbc = odbc_connect ('dd', '', '') or die('¡No se pudo conectar a la base de datos ODBC!');
        $this->mrtienda_path = $mrtienda_path;
        
        $this->File = new GetParams();

        $print = new PrintData($this->ReadFileAsArray($this->File->GetQueryParams('read')));

        
        // $this->FileSelector($this->File->GetQueryParams('read'));
        echo $print->asTable();
        // dd($this->ReadFileAsArray($this->File->GetQueryParams('read')));
    }

    public function FileSelector($file)
    {
        $readFile = null;
        switch ($file) {
            case 'codigos':
                $this->ReadFile('codigos');
                break;
            case 'stocks':
                $this->ReadFile('stocks');
                break;
            case 'valores':
                $this->ReadFile('valores');
                break;
            case 'producto':
                $this->ReadFile('producto');
                break;
            case 'precios':
                $this->ReadFile('precios');
                break;
            case 'config':
                $this->ReadFile('config');
                break;
            
            default:
                $this->ReadFile('codigos');
                break;
        }

        return $readFile;
    }

    public function Files()
    {
        return [
            'DATABASE' => [
                'codigos' => 'CODIGOS.DBF',
                'producto' => 'PRODUCTO.DBF',
                'stocks' => 'STOCKS.DBF',
                'config' => 'config.DBF',
                'precios' => 'PRECIOS.DBF',
            ],
            'REGISTRO' => [
                'config' => 'VALORES.DBF',
            ]
        ];
    }

    public function SetPath($file, $path = '\\DATABASE\\')
    {
        return $this->mrtienda_path . $path . strtoupper($file);
    }

    public function GetFilePath($file)
    {
        $paths = [
            'codigos' => $this->SetPath($file) . '.DBF',
            'producto' => $this->SetPath($file) . '.DBF',
            'stocks' => $this->SetPath($file . '.DBF'),
            'valores' => $this->SetPath($file, '\\REGISTRO\\') . '.DBF',
            'precios' => $this->SetPath($file) . '.DBF',
            'config' => $this->SetPath($file) . '.DBF',
        ];

        return $paths[$file] ?? null;
    }

    public function ReadFile($file)
    {
        $file = $this->GetFilePath($file);

        $dbf = dbase_open($file, 0);
        if ($dbf) {
            $record_numbers = dbase_numrecords($dbf);
            for ($i = 1; $i <= $record_numbers; $i++) {
                $row = dbase_get_record_with_names($dbf, $i);
                echo '<pre>';
                var_dump($row);
                echo '<pre>';
            }
        }
    }

    // Optimize this function
    public function ReadFileAsArray($file)
    {
        $file = $this->GetFilePath($file);
        $dbf = dbase_open($file, 0);
        $fileData = [];
        if ($dbf) {
            $record_numbers = dbase_numrecords($dbf);
            for ($i = 1; $i <= $record_numbers; $i++) {
                $fileData[] = dbase_get_record_with_names($dbf, $i);
            }
            return $fileData;
        }
    }

}

$ReadFile = new GetFileData('C:\MRTIENDA');