<?php

require "vendor/autoload.php";

$client = Elasticsearch\ClientBuilder::create()->build();

$cons = "XLII"; //Término a buscar

$vacias=file("stop.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); //Array palabras vacias

$diccionario = array(); //Diccionario palabras

$ngdArray = array(); //Diccionario palabras candidato ngd

$vacio = false; //Es una palabra vacía?

$numeroResultados = 1000; //Número de resultados a evaluar sus palabras

$palabras = 10; //Número de palabras con mas aparición a evaluar

$cuentaX = 0; //Resultados consulta original

$cuentaY = 0; //Resultados palabras a evaluar

$cuentaXY = 0; //Resultado conjunto palabra + consulta

$numeroTotal = 394990; //Número de tweets

$palabrasFinales = 5; //Número de palabras máximo a introducir en la consulta

$text = ""; //Texto de cada tweet

// 2008-feb-02-04-en/_search?q=text:$cons 
//
$params = [
    "index" => "2008-feb-02-04-en",
    "type" => "tweet",
    "body" => [
	"size" => "1000",
        "query" => [
            "match" => [
                "text" => $cons,
            ]
        ]
    ]
];

$results = $client->search($params);
//print_r($results);

foreach ($vacias as $v) {
	$v = trim($v);
}

foreach ($results as $res) {
	$res = $res['hits'];
	for ($i = 0; $i < sizeof($res); $i++) {
		$resultados = $res[$i];
		$resultados = $resultados['_source'];
		if ($numeroResultados>0) {
			$text = $resultados['text'];
			$textos = split(" ", $text);
			foreach ($textos as $t) {
				foreach ($vacias as $v) {
					if ($v==strtolower($t) || $t=="" || $t=="-" || $t=="." || $t=="(!)" || strpos($cons, $t) || strpos($t, $cons) || $t=="...") {
						$vacio = true;
					}
				}
				if (!$vacio && strtolower($t)!=strtolower($cons)) {
					$diccionario[(string)strtolower($t)] = ($diccionario[(string)strtolower($t)] + 1);
				}
				else {
					$vacio = false;
				}
			}
			$numeroResultados--;
		}
		else {
			break;
		}
	}
}
arsort($diccionario);
$cuentaX = sizeof($res);
foreach ($diccionario as $key => $value) {
	if ($palabras>0) {
		$params = [
			"index" => "2008-feb-02-04-en",
			"type" => "tweet",
			"body" => [
			"size" => "1000",
				"query" => [
					"match" => [
						"text" => $key,
						]
					]
				]
			];
		
		$results = $client->search($params);
		foreach ($results as $res) {
			$res = $res['hits'];
			$cuentaY = sizeof($res);
		}

		$consulta = $cons;
		$consulta .= " ";
		$consulta .= $key;
		
		$params = [
		"index" => "2008-feb-02-04-en",
		"type" => "tweet",
		"body" => [
		"size" => "1000",
			"query" => [
				"match" => [
					"text" => [
						"query" => $consulta,
						"operator" => "and"
						]
					]
				]
			]
		];

		$results = $client->search($params);
		foreach ($results as $res) {
			$res = $res['hits'];
			$cuentaXY = sizeof($res);
		}
		
		$numerador = max(log($cuentaX), log($cuentaY))-log($cuentaXY);
		$denominador = log($numeroTotal) - min(log($cuentaX), log($cuentaY));
		$ngd = $numerador / $denominador;
		$ngdArray[$key] = $ngd;
		$palabras--;
	}
	else {
		break;
	}
}

asort($ngdArray);

foreach ($ngdArray as $key => $value) {
	if ($palabrasFinales>0) {
		$cons .= " ";
		$cons .= $key;
		$palabrasFinales--;
	}
}
print_r ($ngdArray);

$params = [
	"index" => "2008-feb-02-04-en",
	"type" => "tweet",
	"body" => [
	"size" => "1000",
		"query" => [
			"match" => [
				"text" => [
					"query" => $cons,
					"operator" => "or"
					]
				]
			]
		]
	];
	$results = $client->search($params);
	foreach ($results as $res) {
		$res = $res['hits'];
		print(sizeof($res));
	}
	//print_r($results);
?>