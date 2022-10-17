<?php
require __DIR__ . '/vendor/autoload.php';

use Automattic\WooCommerce\Client;
use Automattic\WooCommerce\HttpClient\HttpClientException;

class Sincronizacion
{
    private $url_API;
    private $url_API_woo;
    private $ck_API_woo;
    private $cs_API_woo;

    private $mrTiendaSKUs = array();
    private $woocommerceSKUs = array();
    private $productsMrTienda = array();
   
    public function __construct($url_API, $url_API_woo)
    {
        $this->url_API = $url_API;
        $this->url_API_woo = $url_API_woo;
    }

    public function establecerLlavesWoocommerce($ck_API_woo, $cs_API_woo)
    {
        $this->ck_API_woo = $ck_API_woo;
        $this->cs_API_woo = $cs_API_woo;
    }

    private function connectWoocommerce()
    {
        $woocommerce = new Client(
            $this->url_API_woo,
            $this->ck_API_woo,
            $this->cs_API_woo,
            ['version' => 'wc/v3', 'timeout' => 0]
        );

        return $woocommerce;
    }

    public function comenzarSincronizacion()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $this->url_API);
    
        echo "➜ Obteniendo datos origen ... \n";
        $items_origin = curl_exec($ch);
        curl_close($ch);
    
        if (!$items_origin) {
            exit('❗Error en API origen');
        }

        // Obtenemos datos de la API de origen
        $items_origin = json_decode($items_origin, true);

        $this->fragmentarDatos($items_origin);
    }

    public function fragmentarDatos($items_origin)
    {
        $fragmentos = array();

        foreach (array_chunk($items_origin, 100) as $i) {
            $fragmentos[] = $i;
        }
    
        $array100 = count($fragmentos);

        for ($i = 0; $i < $array100; $i++) {
            $this->sincronizarDatos($fragmentos[$i]);
        }

        // Encontrar diferencias e insertar nuevos productos en e-commerce
        $skuProductosFaltantes = $this->encontrarDiferencias();
        $productosAgregados = 0;
	
        echo PHP_EOL . 'Se encontraron: ' . count($skuProductosFaltantes) . ' diferencias.' . PHP_EOL;

        foreach($skuProductosFaltantes as $skuProductoFaltante) {
            if($this->crearProducto($this->productsMrTienda, $skuProductoFaltante)) {
                $productosAgregados += 1;
            }
        }
    
        echo  PHP_EOL . "Se añadieron: {$productosAgregados} productos nuevos" . PHP_EOL;
    }

    private function crearProducto($missingProducts = array(), $sku) {
        $woocommerce = $this->connectWoocommerce();
    
        foreach($missingProducts as $missingProduct) {
            
            if($missingProduct['sku'] == $sku && !empty($missingProduct['sku']))
            {
                $data = [
                    'sku' => $missingProduct['sku'],
                    'name' => $missingProduct['name'],
                    'type' => 'simple',
                    'regular_price' => strval($missingProduct['price']),
                    'stock_quantity' => $missingProduct['qty'],
                    'manage_stock' => true,
                    'weight' => '1.00' ?? 0
                ];
                
                try
                {
                    echo PHP_EOL . "Agregando el producto: {$data['sku']}." . PHP_EOL;

                    $woocommerce->post('products', $data);
                    return true;
                }
                catch(Exception $e)
                {
                    $evento = "Error en el producto {$data['sku']}: {$e->getMessage()}.";
                    $this->crearEventoRegistro($evento);
                    return false;
                }
            }
        }
        
        return false;
    }

    private function crearEventoRegistro($texto)
    {
        file_put_contents(
            'error.log',
            $texto . PHP_EOL,
            FILE_APPEND
        );
    }

    public function sincronizarDatos($items_origin)
    {
        $woocommerce = $this->connectWoocommerce();

        // formamos el parámetro de lista de SKUs a actualizar
        $param_sku = '';
    
        foreach ($items_origin as $item) {
            $this->mrTiendaSKUs[] = $item['sku']; // Guardamos solo el SKU para recuperarlo
            $this->productsMrTienda[] = $item; // Guardamos toda la informacion del producto
            $param_sku .= $item['sku'] . ',';
        }
    
        echo "➜ Obteniendo los ids de los productos... \n";
		
        // Obtenemos todos los productos de la lista de SKUs
        $products = $woocommerce->get('products/?sku=' . $param_sku . '&per_page=100');
    
        // Construimos la data en base a los productos recuperados
        $item_data = [];

        echo "Actualizando: ";
		
        foreach ($products as $product) {
            $this->woocommerceSKUs[] = $product->sku;
    
            // Filtramos el array de origen por sku
            $sku = $product->sku;
			
			echo "{$sku} ";
            
            $search_item = array_filter($items_origin, function ($item) use ($sku) {
                return $item['sku'] == $sku;
            });

            $search_item = reset($search_item);
    
            // Formamos el array a actualizar
            $item_data[] = [
                'id' => $product->id,
                'name' => $search_item['name'] ?? "",
                'regular_price' => $search_item['price'] ?? "00.0",
                'stock_quantity' => $search_item['qty'] ?? 0,
				'manage_stock' => true,
                'weight' => '1.00'
            ];
        }
    
        // Construimos información a actualizar en lotes
        $data = [
            'update' => $item_data,
        ];
    
        echo "➜ Actualización en lote ... \n";
		
        // Actualización en lotes
		try{
			$result = $woocommerce->post('products/batch', $data);
		}
		catch(Exception $e)
		{
			$evento = 'Error al actualizar los productos:';
			$evento += json_encode($data);
			$evento = 'Fin error al actualizar los prdocutos';
			$this->crearEventoRegistro($evento);
		}
        

        if (!$result) {
            echo ("❗Error al actualizar productos \n");
        } else {
            print("✔ Productos actualizados correctamente \n");
        }
    }

    private function encontrarDiferencias()
    {
        return array_diff($this->mrTiendaSKUs, $this->woocommerceSKUs);
    }
}

$sincronizacion = new Sincronizacion(
    'http://localhost:8000/index.php',
    'https://www.lacontravinos.com/ensenada/'
);

$sincronizacion->establecerLlavesWoocommerce(
    'ck_18206408153ba6c67884213e49218697b8d6eb8c',
    'cs_bb8ec6757c8e72b111943768676849123cfd60c2'
);

$sincronizacion->comenzarSincronizacion();