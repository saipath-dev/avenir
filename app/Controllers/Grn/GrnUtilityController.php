<?php

namespace App\Controllers\Grn;

use App\Controllers\BaseController;

class GrnUtilityController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    public function hardcoded_data()
    {
        return [
            "supplier" => [
                "supplier_email" => [
                    "text" => "hkmotors505@gmail.com",
                    "ratio" => 0.79,
                ],
                "supplier_phone" => ["text" => "0777411229", "ratio" => 0.74],
                "supplier_address" => [
                    "text" => "No: 41/2A Yagghapitiya, Gunnepana, kandy.",
                    "ratio" => 0.36,
                ],
                "supplier_name" => ["text" => "H.K MOTORS", "ratio" => 0.24],
            ],
            "tableData" => [
                [
                    "text" => "3 47500.00 142500.00 1512 P.S.T PUMP ASSY",
                    "ratio" => 1,
                    "properties" => [
                        [
                            "type" => "line_item/quantity",
                            "text" => "3",
                            "value" => 3,
                            "ratio" => 0.87,
                        ],
                        [
                            "type" => "line_item/unit_price",
                            "text" => "47500.00",
                            "value" => 47500,
                            "ratio" => 0.94,
                        ],
                        [
                            "type" => "line_item/amount",
                            "text" => "142500.00",
                            "value" => 142500,
                            "ratio" => 0.95,
                        ],
                        [
                            "type" => "line_item/product_code",
                            "text" => "1512",
                            "value" => 1512,
                            "ratio" => 0.55,
                        ],
                        [
                            "type" => "line_item/description",
                            "text" => "P.S.T PUMP ASSY",
                            "value" => 0,
                            "ratio" => 0.65,
                        ],
                    ],
                ],
                [
                    "text" => "31000.000 2 62000.00 1527 P.S.T PUMP ASSY ZF",
                    "ratio" => 1,
                    "properties" => [
                        [
                            "type" => "line_item/unit_price",
                            "text" => "31000.000",
                            "value" => 31000,
                            "ratio" => 0.95,
                        ],
                        [
                            "type" => "line_item/quantity",
                            "text" => "2",
                            "value" => 2,
                            "ratio" => 0.87,
                        ],
                        [
                            "type" => "line_item/amount",
                            "text" => "62000.00",
                            "value" => 62000,
                            "ratio" => 0.95,
                        ],
                        [
                            "type" => "line_item/product_code",
                            "text" => "1527",
                            "value" => 1527,
                            "ratio" => 0.49,
                        ],
                        [
                            "type" => "line_item/description",
                            "text" => "P.S.T PUMP ASSY ZF",
                            "value" => 0,
                            "ratio" => 0.68,
                        ],
                    ],
                ],
                [
                    "text" => "850.00 6 5100.00 1523 TEMPERATURE SOCKET TATA ACE",
                    "ratio" => 1,
                    "properties" => [
                        [
                            "type" => "line_item/unit_price",
                            "text" => "850.00",
                            "value" => 850,
                            "ratio" => 0.91,
                        ],
                        [
                            "type" => "line_item/quantity",
                            "text" => "6",
                            "value" => 6,
                            "ratio" => 0.86,
                        ],
                        [
                            "type" => "line_item/amount",
                            "text" => "5100.00",
                            "value" => 5100,
                            "ratio" => 0.95,
                        ],
                        [
                            "type" => "line_item/product_code",
                            "text" => "1523",
                            "value" => 1523,
                            "ratio" => 0.64,
                        ],
                        [
                            "type" => "line_item/description",
                            "text" => "TEMPERATURE SOCKET TATA ACE",
                            "value" => 0,
                            "ratio" => 0.59,
                        ],
                    ],
                ],
                [
                    "text" => "LOCK WITH WASHER TATA ACE 6 7500.00 1250.00",
                    "ratio" => 1,
                    "properties" => [
                        [
                            "type" => "line_item/description",
                            "text" => "LOCK WITH WASHER TATA ACE",
                            "value" => 0,
                            "ratio" => 0.58,
                        ],
                        [
                            "type" => "line_item/quantity",
                            "text" => "6",
                            "value" => 6,
                            "ratio" => 0.74,
                        ],
                        [
                            "type" => "line_item/amount",
                            "text" => "7500.00",
                            "value" => 7500,
                            "ratio" => 0.94,
                        ],
                        [
                            "type" => "line_item/unit_price",
                            "text" => "1250.00",
                            "value" => 1250,
                            "ratio" => 0.92,
                        ],
                    ],
                ],
                [
                    "text" =>
                    "4 DIESEL FLOATER ONLY TATA 407 VEETREE 1519 2950.00 11800.00",
                    "ratio" => 1,
                    "properties" => [
                        [
                            "type" => "line_item/quantity",
                            "text" => "4",
                            "value" => 4,
                            "ratio" => 0.72,
                        ],
                        [
                            "type" => "line_item/description",
                            "text" => "DIESEL FLOATER ONLY TATA 407 VEETREE",
                            "value" => 0,
                            "ratio" => 0.69,
                        ],
                        [
                            "type" => "line_item/product_code",
                            "text" => "1519",
                            "value" => 1519,
                            "ratio" => 0.79,
                        ],
                        [
                            "type" => "line_item/unit_price",
                            "text" => "2950.00",
                            "value" => 2950,
                            "ratio" => 0.91,
                        ],
                        [
                            "type" => "line_item/amount",
                            "text" => "11800.00",
                            "value" => 11800,
                            "ratio" => 0.95,
                        ],
                    ],
                ],
                [
                    "text" => "7250.00 1450 00->
1514 1450 00- BELT AJUSTER FORK S/ASCE 5",
                    "ratio" => 1,
                    "properties" => [
                        [
                            "type" => "line_item/amount",
                            "text" => "7250.00",
                            "value" => 7250,
                            "ratio" => 0.94,
                        ],
                        [
                            "type" => "line_item/product_code",
                            "text" => "1450 00->
1514",
                            "value" => 1450,
                            "ratio" => 0.63,
                        ],
                        [
                            "type" => "line_item/unit_price",
                            "text" => "1450 00-",
                            "value" => 1450,
                            "ratio" => 0.83,
                        ],
                        [
                            "type" => "line_item/description",
                            "text" => "BELT AJUSTER FORK S/ASCE",
                            "value" => 0,
                            "ratio" => 0.67,
                        ],
                        [
                            "type" => "line_item/quantity",
                            "text" => "5",
                            "value" => 5,
                            "ratio" => 0.65,
                        ],
                    ],
                ],
                [
                    "text" => "1525 8 40800.00 TURBO HOSE EXPRESS 5100.00",
                    "ratio" => 1,
                    "properties" => [
                        [
                            "type" => "line_item/product_code",
                            "text" => "1525",
                            "value" => 1525,
                            "ratio" => 0.8,
                        ],
                        [
                            "type" => "line_item/quantity",
                            "text" => "8",
                            "value" => 8,
                            "ratio" => 0.75,
                        ],
                        [
                            "type" => "line_item/amount",
                            "text" => "40800.00",
                            "value" => 40800,
                            "ratio" => 0.94,
                        ],
                        [
                            "type" => "line_item/description",
                            "text" => "TURBO HOSE EXPRESS",
                            "value" => 0,
                            "ratio" => 0.64,
                        ],
                        [
                            "type" => "line_item/unit_price",
                            "text" => "5100.00",
                            "value" => 5100,
                            "ratio" => 0.88,
                        ],
                    ],
                ],
                [
                    "text" => "B
1518 GEAR LEVER BRAKET XENON 6 1950.00 11700.00",
                    "ratio" => 1,
                    "properties" => [
                        [
                            "type" => "line_item/product_code",
                            "text" => "B
1518",
                            "value" => 0,
                            "ratio" => 0.65,
                        ],
                        [
                            "type" => "line_item/description",
                            "text" => "GEAR LEVER BRAKET XENON",
                            "value" => 0,
                            "ratio" => 0.74,
                        ],
                        [
                            "type" => "line_item/quantity",
                            "text" => "6",
                            "value" => 6,
                            "ratio" => 0.74,
                        ],
                        [
                            "type" => "line_item/unit_price",
                            "text" => "1950.00",
                            "value" => 1950,
                            "ratio" => 0.9,
                        ],
                        [
                            "type" => "line_item/amount",
                            "text" => "11700.00",
                            "value" => 11700,
                            "ratio" => 0.94,
                        ],
                    ],
                ],
                [
                    "text" =>
                    "1528 TIMING COVER TATA S/ACE SET OF 3 1 2150.00 2150.00-",
                    "ratio" => 1,
                    "properties" => [
                        [
                            "type" => "line_item/product_code",
                            "text" => "1528",
                            "value" => 1528,
                            "ratio" => 0.77,
                        ],
                        [
                            "type" => "line_item/description",
                            "text" => "TIMING COVER TATA S/ACE SET OF 3",
                            "value" => 0,
                            "ratio" => 0.63,
                        ],
                        [
                            "type" => "line_item/quantity",
                            "text" => "1",
                            "value" => 1,
                            "ratio" => 0.83,
                        ],
                        [
                            "type" => "line_item/amount",
                            "text" => "2150.00",
                            "value" => 2150,
                            "ratio" => 0.95,
                        ],
                        [
                            "type" => "line_item/unit_price",
                            "text" => "2150.00-",
                            "value" => 2150,
                            "ratio" => 0.91,
                        ],
                    ],
                ],
                [
                    "text" => "1520 MANIFOLD PAKING MARUTI 800 O/M 10 725.00 7250.00",
                    "ratio" => 1,
                    "properties" => [
                        [
                            "type" => "line_item/product_code",
                            "text" => "1520",
                            "value" => 1520,
                            "ratio" => 0.66,
                        ],
                        [
                            "type" => "line_item/description",
                            "text" => "MANIFOLD PAKING MARUTI 800 O/M",
                            "value" => 0,
                            "ratio" => 0.67,
                        ],
                        [
                            "type" => "line_item/quantity",
                            "text" => "10",
                            "value" => 10,
                            "ratio" => 0.79,
                        ],
                        [
                            "type" => "line_item/unit_price",
                            "text" => "725.00",
                            "value" => 725,
                            "ratio" => 0.85,
                        ],
                        [
                            "type" => "line_item/amount",
                            "text" => "7250.00",
                            "value" => 7250,
                            "ratio" => 0.94,
                        ],
                    ],
                ],
                [
                    "text" => "2650.00 4 HOSE STEEL LONG TATA ACE 10600.00 1513",
                    "ratio" => 1,
                    "properties" => [
                        [
                            "type" => "line_item/unit_price",
                            "text" => "2650.00",
                            "value" => 2650,
                            "ratio" => 0.86,
                        ],
                        [
                            "type" => "line_item/quantity",
                            "text" => "4",
                            "value" => 4,
                            "ratio" => 0.72,
                        ],
                        [
                            "type" => "line_item/description",
                            "text" => "HOSE STEEL LONG TATA ACE",
                            "value" => 0,
                            "ratio" => 0.56,
                        ],
                        [
                            "type" => "line_item/amount",
                            "text" => "10600.00",
                            "value" => 10600,
                            "ratio" => 0.94,
                        ],
                        [
                            "type" => "line_item/product_code",
                            "text" => "1513",
                            "value" => 1513,
                            "ratio" => 0.67,
                        ],
                    ],
                ],
                [
                    "text" => "W",
                    "ratio" => 1,
                    "properties" => [
                        [
                            "type" => "line_item/quantity",
                            "text" => "W",
                            "value" => 0,
                            "ratio" => 0.39,
                        ],
                    ],
                ],
            ],
        ];
    }
}
