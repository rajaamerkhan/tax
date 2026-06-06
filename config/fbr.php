<?php

return [
    'env' => env('FBR_ENV', 'sandbox'),
    'sandbox_base_url' => env('FBR_SANDBOX_BASE_URL'),
    'production_base_url' => env('FBR_PRODUCTION_BASE_URL'),
    'security_token' => env('FBR_SECURITY_TOKEN'),
    'verify_url' => env('FBR_VERIFY_URL'),
    'endpoints' => [
        'validate_invoice' => env('FBR_VALIDATE_ENDPOINT', '/di_data/v1/di/validateinvoicedata_sb'),
        'submit_invoice' => env('FBR_SUBMIT_ENDPOINT', '/di_data/v1/di/postinvoicedata_sb'),
        'province_codes' => env('FBR_ENDPOINT_PROVINCES', '/pdi/v1/provinces'),
        'document_types' => env('FBR_ENDPOINT_DOCUMENT_TYPES', '/pdi/v1/doctypecode'),
        'item_codes' => env('FBR_ENDPOINT_ITEM_CODES', '/pdi/v1/itemdesccode'),
        'sro_item_ids' => env('FBR_ENDPOINT_SRO_ITEM_IDS', '/pdi/v1/sroitemcode'),
        'transaction_type_ids' => env('FBR_ENDPOINT_TRANSACTION_TYPES', '/pdi/v1/transtypecode'),
        'uom_ids' => env('FBR_ENDPOINT_UOMS', '/pdi/v1/uom'),
        'sro_schedule' => env('FBR_ENDPOINT_SRO_SCHEDULES', '/pdi/v1/SroSchedule'),
        'rate_ids' => env('FBR_ENDPOINT_RATE_IDS', '/pdi/v2/SaleTypeToRate'),
        'hs_codes_with_uom' => env('FBR_ENDPOINT_HS_CODES', '/pdi/v2/HS_UOM'),
        'sro_item_detail_ids' => env('FBR_ENDPOINT_SRO_ITEMS', '/pdi/v2/SROItem'),
        'st_atl' => env('FBR_ENDPOINT_ST_ATL', '/dist/v1/statl'),
        'registration_type' => env('FBR_ENDPOINT_REGISTRATION_TYPE', '/dist/v1/Get_Reg_Type'),
    ],
];
