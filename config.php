<?php
	date_default_timezone_set('Europe/Moscow');
	// HTTP
	define('HTTP_SERVER', 'http://kids-universe.ru/');
	// constants
	define('MY_TRUE', utf8_encode("&#10004;"));
	define('MY_FALSE', utf8_encode("&#10008;"));
	
	// MS
	//новый
	define('MS_NEW_STATE', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/dd93e5be-4f86-11e6-7a69-8f5500000968'); //новый
	define('MS_NEW_STATE_ID', 'dd93e5be-4f86-11e6-7a69-8f5500000968'); //новый
	
	define('MS_MPNEW_STATE', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/22a29bbb-0176-11e9-912f-f3d400132dd9'); // новый (маркетплейс)
	define('MS_MPNEW_STATE_ID', '22a29bbb-0176-11e9-912f-f3d400132dd9'); // новый (маркетплейс)
	
	define('MS_CONFIRM_STATE', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/dd93ea57-4f86-11e6-7a69-8f5500000969'); // подтвержден
	define('MS_CONFIRM_STATE_ID', 'dd93ea57-4f86-11e6-7a69-8f5500000969'); // подтвержден
	
	// подтвержден (маркетплейс)
	define('MS_CONFIRMGOODS_STATE', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/9d61e479-013c-11e9-9107-504800115e4b');
	define('MS_CONFIRMBERU_STATE', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/9d61e479-013c-11e9-9107-504800115e4b');
	define('MS_CONFIRMBERU_STATE_ID', '9d61e479-013c-11e9-9107-504800115e4b');
	// Собран (Доставка)
	define('MS_PACKEDDELIVERY_STATE', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/dd93eed1-4f86-11e6-7a69-8f550000096a');
	define('MS_PACKEDDELIVERY_STATE_ID', 'dd93eed1-4f86-11e6-7a69-8f550000096a');
	// Собран (Маркетплейс)
	define('MS_SHIP_STATE', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/dd93eed1-4f86-11e6-7a69-8f550000096a');
	define('MS_PACKED_STATE', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/1ff694dd-edd2-11e8-9107-5048000d2248');
	define('MS_PACKEDMP_STATE', '1ff694dd-edd2-11e8-9107-5048000d2248');
	// Собран (Самовывоз)
	define('MS_SHIPPICKUP_STATE', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/c88ff31b-0e55-11e7-7a31-d0fd0018ad64');
	define('MS_SHIPGOODS_STATE', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/1ff694dd-edd2-11e8-9107-5048000d2248');
	// отгружен	
	define('MS_SHIPPED_STATE', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/dd93f2b4-4f86-11e6-7a69-8f550000096b');
	define('MS_SHIPPED_STATE_ID', 'dd93f2b4-4f86-11e6-7a69-8f550000096b');
	// отгружен (маркетплейс)
	define('MS_SHIPPED_MP_STATE', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/d75a2136-edd0-11e8-9ff4-34e8000d3e7b');
	define('MS_PAYIN_STATE', 'https://api.moysklad.ru/api/remap/1.2/entity/paymentin/metadata/states/0b17ad95-1b70-11e7-7a34-5acf0007769c');
	// отменен
	define('MS_CANCEL_STATE', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/dd940025-4f86-11e6-7a69-8f550000096e');
	define('MS_CANCEL_STATE_ID', 'dd940025-4f86-11e6-7a69-8f550000096e');
	// доставлен
	define('MS_DELIVERED_STATE', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/dd93f734-4f86-11e6-7a69-8f550000096c');
	define('MS_DELIVERED_STATE_ID', 'dd93f734-4f86-11e6-7a69-8f550000096c');
	// уточнение
	define('MS_DEFFERED_STATE_ID', '624309f7-0a4e-11e7-7a31-d0fd00101f02');
	
	define('MS_SHIPTYPE_ATTR', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/attributes/5c01b362-d61f-11e8-9107-504800214d3f');
	define('MS_SHIPTYPE_ATTR_ID', '5c01b362-d61f-11e8-9107-504800214d3f');
	define('MS_SHIPTYPE_CURIER0', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/1a048b1f-d61f-11e8-9109-f8fc0021c485/b6108feb-369d-11e9-9ff4-34e80012330e');
	define('MS_SHIPTYPE_CURIER0_ID', 'b6108feb-369d-11e9-9ff4-34e80012330e');
	define('MS_SHIPTYPE_CURIER1', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/1a048b1f-d61f-11e8-9109-f8fc0021c485/098b7ab9-d690-11e8-9109-f8fc0001dbab');
	define('MS_SHIPTYPE_CURIER2', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/1a048b1f-d61f-11e8-9109-f8fc0021c485/e54aece8-22f2-11e9-912f-f3d400187422');
	define('MS_SHIPTYPE_CURIER3', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/1a048b1f-d61f-11e8-9109-f8fc0021c485/3236fe8d-d61f-11e8-9ff4-34e800217014');
	define('MS_SHIPTYPE_CURIER4', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/1a048b1f-d61f-11e8-9109-f8fc0021c485/1fad98f7-7ad2-11e9-9107-504800157b1f');
	define('MS_SHIPTYPE_CURIER5', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/1a048b1f-d61f-11e8-9109-f8fc0021c485/125e4e5a-f566-11e8-912f-f3d4000be54e');
	define('MS_SHIPTYPE_CURIER10', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/1a048b1f-d61f-11e8-9109-f8fc0021c485/3f9ed0e7-d61f-11e8-9109-f8fc0021c54e');
	define('MS_SHIPTYPE_IML', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/1a048b1f-d61f-11e8-9109-f8fc0021c485/148563b3-f157-11e8-9107-5048001cbae0');
	define('MS_SHIPTYPE_OZON', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/1a048b1f-d61f-11e8-9109-f8fc0021c485/3172d6aa-6fac-11ea-0a80-02c2000cf9f2');
	define('MS_SHIPTYPE_PICKUP', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/1a048b1f-d61f-11e8-9109-f8fc0021c485/cc2963fb-d620-11e8-9109-f8fc0021cffa');
	define('MS_SHIPTYPE_PICKUP_ID', 'cc2963fb-d620-11e8-9109-f8fc0021cffa');
	define('MS_SHIPTYPE_BERU', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/1a048b1f-d61f-11e8-9109-f8fc0021c485/ec17ba6f-f3fd-11e9-0a80-0477001d4b07');
	define('MS_SHIPTYPE_GOODS', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/1a048b1f-d61f-11e8-9109-f8fc0021c485/f68592ef-dd36-11e8-9ff4-34e800188647');
	define('MS_SHIPTYPE_WB', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/1a048b1f-d61f-11e8-9109-f8fc0021c485/0218e923-6b7d-11eb-0a80-06e50006ee79');

	define('MS_MPCANCEL_ATTR', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/attributes/05d3f45a-518d-11e9-9109-f8fc000a2635');
	define('MS_MPCANCEL_ATTR_ID', '05d3f45a-518d-11e9-9109-f8fc000a2635');
	define('MS_BARCODE_ATTR', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/attributes/51ec2167-e895-11e8-9ff4-31500000db84');//штрихкод
	define('MS_BARCODE_ATTR_ID', '51ec2167-e895-11e8-9ff4-31500000db84');//штрихкод
	
	define('MS_ATTR', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/attributes/');
	define('MS_DELIVERY_ATTR', '5c01b362-d61f-11e8-9107-504800214d3f');
	define('MS_DELIVERYTIME_ATTR', '1f394750-d62e-11e8-9ff4-3150002139c8');
	define('MS_DELIVERYSERVICE_ATTR', 'e8df1e60-b268-11ea-0a80-052600006e80');
	define('MS_DELIVERYNUMBER_ATTR', '63012e3e-e791-11e8-9109-f8fc000cf9e1'); //номер доставки
	define('MS_PAYMENTTYPE_ATTR', '2ada6f00-d623-11e8-9109-f8fc0021e4d1');
	define('MS_FIO_ATTR', 'd948e4fe-d621-11e8-9ff4-34e80021aea5');
	define('MS_PHONE_ATTR', '5f9f5c95-d622-11e8-9ff4-34e80021bc5f');
	define('MS_ADDRESS_ATTR', 'b73f3a67-d62e-11e8-9109-f8fc002175da');
	define('MS_CANCEL_ATTR', '05d3f45a-518d-11e9-9109-f8fc000a2635');
	define('MS_BARCODE2_ATTR', '51ec2167-e895-11e8-9ff4-31500000db84'); //штрихкод
	define('MS_PLACECOUNT_ATTR', '5f9f614a-d622-11e8-9ff4-34e80021bc61');
	define('MS_MPAMOUNT_ATTR', '4f44dd34-decb-11e8-9ff4-34e8000b2f2d');
	define('MS_PAIDBYMP_ATTR', 'ce57828b-81f9-11ec-0a80-09a4003763ff');
	define('MS_WB_FILE_ATTR', 'edac7e59-7c48-11ef-0a80-06b70001e22a');
	
	define('MS_DELIVERYTIME_9_21', 'https://api.moysklad.ru/api/remap/1.1/entity/customentity/e7a5f365-d62d-11e8-9107-50480021c6c8/e0460dd7-d62e-11e8-9ff4-34e8002207d8');
	define('MS_DELIVERYTIME_9_21_ID', 'e0460dd7-d62e-11e8-9ff4-34e8002207d8');
	
	define('MS_PAYMENTTYPE_SBERBANK', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/e0430541-d622-11e8-9109-f8fc00212299/27155816-dd0b-11e8-9109-f8fc0015616b');
	define('MS_PAYMENTTYPE_SBERBANK_ID', '27155816-dd0b-11e8-9109-f8fc0015616b');
	define('MS_PAYMENTTYPE_SBERBANK_ONLINE', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/e0430541-d622-11e8-9109-f8fc00212299/14876972-d623-11e8-9109-f8fc00212414');
	define('MS_PAYMENTTYPE_SBERBANK_ONLINE_ID', '14876972-d623-11e8-9109-f8fc00212414');
	
	define('MS_DELIVERY_VALUE0', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/1a048b1f-d61f-11e8-9109-f8fc0021c485/b6108feb-369d-11e9-9ff4-34e80012330e');
	define('MS_DELIVERY_VALUE_WB', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/1a048b1f-d61f-11e8-9109-f8fc0021c485/0218e923-6b7d-11eb-0a80-06e50006ee79');
	define('MS_DELIVERY_VALUE_RPOST', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/1a048b1f-d61f-11e8-9109-f8fc0021c485/6eee2db2-efe8-11ef-0a80-13b40001c620');
	define('MS_DELIVERY_VALUE_SDEK', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/1a048b1f-d61f-11e8-9109-f8fc0021c485/e1b429c5-efe8-11ef-0a80-14130001f232');
	define('MS_DELIVERY_VALUE_YANDEX', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/1a048b1f-d61f-11e8-9109-f8fc0021c485/06283054-efe9-11ef-0a80-030c0001f22e');
	define('MS_DELIVERY_VALUEALI', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/1a048b1f-d61f-11e8-9109-f8fc0021c485/0bc2bb48-1a65-11eb-0a80-050d0001b40b');
	define('MS_DELIVERYTIME_VALUE1', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/e7a5f365-d62d-11e8-9107-50480021c6c8/e0460dd7-d62e-11e8-9ff4-34e8002207d8');
	define('MS_PAYMENTTYPE_VALUE1', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/e0430541-d622-11e8-9109-f8fc00212299/14876972-d623-11e8-9109-f8fc00212414');
	define('MS_PAYMENTTYPE_CASH', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/e0430541-d622-11e8-9109-f8fc00212299/f58e7f97-d622-11e8-9109-f8fc00212380');
	define('MS_PAYMENTTYPE_CASH_ID', 'f58e7f97-d622-11e8-9109-f8fc00212380');
	
	define('MS_DELIVERY_TIME0', 'https://api.moysklad.ru/api/remap/1.2/entity/customentity/e7a5f365-d62d-11e8-9107-50480021c6c8/93c67dd6-f361-11e9-0a80-00a70014e000');

	define('MS_GOODS_AGENT', 'https://api.moysklad.ru/api/remap/1.2/entity/counterparty/b05fbd35-dd08-11e8-9107-5048001507ff');
	define('MS_GOODS_AGENT_ID', 'b05fbd35-dd08-11e8-9107-5048001507ff');
	define('MS_BERU_AGENT', 'https://api.moysklad.ru/api/remap/1.2/entity/counterparty/a9b9199b-999f-11e9-912f-f3d4000680d8');
	define('MS_OZON_AGENT', 'https://api.moysklad.ru/api/remap/1.2/entity/counterparty/e1490fb8-7054-11ea-0a80-01220017723b');
	define('MS_OZON_AGENT_ID', 'e1490fb8-7054-11ea-0a80-01220017723b');
	define('MS_ALI_AGENT', 'https://api.moysklad.ru/api/remap/1.2/entity/counterparty/ad56b3e8-e907-11ea-0a80-00e9000564f1');
	define('MS_WB_AGENT', 'https://api.moysklad.ru/api/remap/1.2/entity/counterparty/2a755972-39e1-11ea-0a80-03840002a06c');

	// организации
	// 10kids
	define('MS_10KIDS', 'https://api.moysklad.ru/api/remap/1.2/entity/organization/5693a7da-774a-11e9-9ff4-34e80015f08f');
	define('MS_10KIDS_ID', '5693a7da-774a-11e9-9ff4-34e80015f08f');
	define('MS_10KIDS_ACCOUNT', '5693b534-774a-11e9-9ff4-34e80015f090');
	// 4cleaning
	define('MS_4CLEANING', 'https://api.moysklad.ru/api/remap/1.2/entity/organization/dd78611c-4f86-11e6-7a69-8f550000094b');
	define('MS_4CLEANING_OWNER', 'https://api.moysklad.ru/api/remap/1.2/entity/employee/dd678bd7-4f86-11e6-7a69-8f5500000917');
	define('MS_4CLEANING_ACCOUNT', 'dd786931-4f86-11e6-7a69-8f550000094c');
	define('MS_ULLO', 'https://api.moysklad.ru/api/remap/1.2/entity/organization/f3e8ac0c-62ad-11ea-0a80-03e30022f0a0');
	define('MS_ULLO_ID', 'f3e8ac0c-62ad-11ea-0a80-03e30022f0a0');
	define('MS_ULLO_OWNER', 'https://api.moysklad.ru/api/remap/1.2/entity/employee/dd678bd7-4f86-11e6-7a69-8f5500000917');
	define('MS_ULLO_ACCOUNT', 'f3e8b0c9-62ad-11ea-0a80-03e30022f0a1');
	define('MS_KAORI', 'https://api.moysklad.ru/api/remap/1.2/entity/organization/cb72811a-5fac-11ea-0a80-01a1000989c6');
	define('MS_KAORI_ID', 'cb72811a-5fac-11ea-0a80-01a1000989c6');
	define('MS_IPGYUMYUSH', 'https://api.moysklad.ru/api/remap/1.2/entity/organization/db56de38-decd-11e9-0a80-021600094663');
	define('MS_PROFIT', 'https://api.moysklad.ru/api/remap/1.2/entity/organization/dd78611c-4f86-11e6-7a69-8f550000094b');
	define('MS_ALIANS', 'https://api.moysklad.ru/api/remap/1.2/entity/organization/e7cc6138-2a6a-11eb-0a80-0515000dac01');
	define('MS_ALIANS_ID', 'e7cc6138-2a6a-11eb-0a80-0515000dac01');
	define('MS_KOSMOS', 'https://api.moysklad.ru/api/remap/1.2/entity/organization/3701ecf6-e768-11ee-0a80-16b3000afd46');
	define('MS_KOSMOS_ID', '3701ecf6-e768-11ee-0a80-16b3000afd46');
	define('MS_IP_PURTOV', 'https://api.moysklad.ru/api/remap/1.2/entity/organization/befd9ebb-eba0-11ee-0a80-1751000160a9');
	define('MS_IP_PURTOV_ID', 'befd9ebb-eba0-11ee-0a80-1751000160a9');
	define('MS_VES', 'https://api.moysklad.ru/api/remap/1.2/entity/organization/2d42ae0f-ed4e-11ee-0a80-01e7000235af');
	define('MS_VES_ID', '2d42ae0f-ed4e-11ee-0a80-01e7000235af');
	define('MS_APOLLON', 'https://api.moysklad.ru/api/remap/1.2/entity/organization/8c393e3f-0b14-11ef-0a80-0d90002fd6ad');
	define('MS_APOLLON_ID', '8c393e3f-0b14-11ef-0a80-0d90002fd6ad');
	define('MS_PLUTON', 'https://api.moysklad.ru/api/remap/1.2/entity/organization/10c90779-1c14-11ef-0a80-0db7003c55fc');
	define('MS_PLUTON_ID', '10c90779-1c14-11ef-0a80-0db7003c55fc');
	define('MS_VYSOTA', 'https://api.moysklad.ru/api/remap/1.2/entity/organization/2b87b91d-1d40-11ef-0a80-073200020b17');
	define('MS_VYSOTA_ID', '2b87b91d-1d40-11ef-0a80-073200020b17');
	
	define('MS_REP_4CLEANING', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/customtemplate/6947deff-5737-4851-a2c1-6c0913fdf871');
	define('MS_REP_10KIDS', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/customtemplate/920179aa-951b-4000-ba50-dfe9b1a89dd6');
	define('MS_REP_GOODS', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/customtemplate/a17ab0fc-b8b8-4bad-b54d-78d78c7e0ac4');
	define('MS_REP_BERU', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/customtemplate/4d4f5e1e-2c21-4549-977a-65b177ac7d23');

	define('MS_COURL', 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/');
	define('MS_PAYINURL', 'https://api.moysklad.ru/api/remap/1.2/entity/paymentin/');
	define('MS_STOCKURL', 'https://api.moysklad.ru/api/remap/1.2/report/stock/all');
	define('MS_ASSORTURL', 'https://api.moysklad.ru/api/remap/1.2/entity/assortment');
	define('MS_PRODUCTURL', 'https://api.moysklad.ru/api/remap/1.2/entity/product/');
	define('MS_SERVICEURL', 'https://api.moysklad.ru/api/remap/1.2/entity/service/');
	define('MS_SRURL', 'https://api.moysklad.ru/api/remap/1.2/entity/salesreturn/');
	define('MS_DEMANDURL', 'https://api.moysklad.ru/api/remap/1.2/entity/demand/');

	define('MS_API_BASE_URL', 'https://api.moysklad.ru/api/remap');
	define('MS_API_VERSION_1_1', '/1.1');
	define('MS_API_VERSION_1_2', '/1.2');
	define('MS_API_ASSORTMENT', '/entity/assortment');
	define('MS_API_PRODUCT', '/entity/product');
	define('MS_API_PRICETYPES', '/context/companysettings/pricetype');
	define('MS_API_SERVICE', '/entity/service');
	define('MS_API_STATE', '/entity/state');
	define('MS_API_CUSTOMERORDER', '/entity/customerorder');
	define('MS_API_CUSTOMERORDER_POSITIONS', '/positions');
	define('MS_API_DEMAND', '/entity/demand');
	define('MS_API_COUNTERPARTY', '/entity/counterparty');
	define('MS_API_CUSTOMERORDERSTATE', '/entity/customerorder/metadata/states');
	define('MS_API_METADATA', '/metadata');
	define('MS_API_CUSTOMERENTITY', '/entity/customentity');
	define('MS_API_ORGANIZATION', '/entity/organization');
	define('MS_API_CUSTOMERREPORTS', '/metadata/customtemplate');
	define('MS_API_ATTRIBUTES', '/metadata/attributes');
	define('MS_API_PAYMENTIN', '/entity/paymentin');
	define('MS_API_PROJECT', '/entity/project');
	define('MS_API_STORE', '/entity/store');
	define('MS_API_GROUP', '/entity/group');

	define('MS_API_ATTRIBUTE_CURIER', '1a048b1f-d61f-11e8-9109-f8fc0021c485');
	define('MS_API_ATTRIBUTE_DELIVERYTIME', 'e7a5f365-d62d-11e8-9107-50480021c6c8');
	define('MS_API_ATTRIBUTE_PAYMENTTYPE', 'e0430541-d622-11e8-9109-f8fc00212299');
	
	define('MS_API_PAYMENTIN_ATTRIBUTE_AMOUNT', '9b565d44-8745-11ea-0a80-05e80010dfa6');
	define('MS_API_PAYMENTIN_ATTRIBUTE_PAYTYPE', '58fcc5f5-87e1-11ea-0a80-014d00155628');
	define('MS_API_PAYMENTIN_ATTRIBUTE_STORNONUMBER', '58fcc195-87e1-11ea-0a80-014d00155626');
	define('MS_API_PAYMENTIN_ATTRIBUTE_STORNODATE', '58fcc4fa-87e1-11ea-0a80-014d00155627');
	
	define('MS_API_CUSTOMERORDER_ATTRIBUTE_TKCOMISSION', '65f25c16-e467-11e9-0a80-0191000b2afe');
	define('MS_API_CUSTOMERORDER_ATTRIBUTE_TCOMISSION', '65f25f25-e467-11e9-0a80-0191000b2aff');
	define('MS_API_CUSTOMERORDER_ATTRIBUTE_PLCOMISSION', '30c174f0-e684-11e9-0a80-05f20003976b');
	define('MS_API_CUSTOMERORDER_ATTRIBUTE_LCOMISSION', '2820265f-bef5-11ea-0a80-01ba001c11e1');

	define('MS_PRODUCT_CATEGORY_ATTR', '6f1c1a88-d34b-11e9-0a80-03e80000108f');
	define('MS_PRODUCT_CATEGORY_CE', '55725554-d34b-11e9-0a80-026900001178');
	define('MS_PRODUCT_CATEGORY_OTHERS', ['99e9407e-d34b-11e9-0a80-0199000014b9', '9f17d29d-d34b-11e9-0a80-0460000016ec', 'a9ccc6a8-d34c-11e9-0a80-019900001637', 'abae58c8-d34b-11e9-0a80-03e8000010ba', 'b4eaba89-d34c-11e9-0a80-054700001558', 'b9de9cce-d34b-11e9-0a80-04600000172b']);
	define('MS_PRODUCT_CATEGORY_COSMETICS', ['2a40182e-4063-11ea-0a80-01cf0009bf83', '8d0b57f2-d34b-11e9-0a80-007d000013a9']);
	define('MS_PRODUCT_CATEGORY_DIAPERS', ['e178c3b9-d34b-11e9-0a80-01990000153f']);

	define('MS_CATCOMM', '65f25c16-e467-11e9-0a80-0191000b2afe');
	define('MS_TRCOMM', '65f25f25-e467-11e9-0a80-0191000b2aff');
	define('MS_PLCOMM', '30c174f0-e684-11e9-0a80-05f20003976b');
	define('MS_LOGCOMM', '2820265f-bef5-11ea-0a80-01ba001c11e1');
	define('MS_CANCELLED', '05d3f45a-518d-11e9-9109-f8fc000a2635');
	define('MS_LIMIT', 100);
	define('MS_DELIVERY_SERVICE', '000051');
	define('MS_SELFDELIVERY_SERVICE', '00001');
	define('MS_PICKUP_SERVICE', '00002');
	define('MS_DELIVERY_SERVICE_ID', '0828ec5c-f8bf-11ea-0a80-0079000560a3');

	define('MS_STORE', 'https://api.moysklad.ru/api/remap/1.2/entity/store/dd7ce917-4f86-11e6-7a69-8f550000094d');
	define('MS_API_STORE_ID', 'dd7ce917-4f86-11e6-7a69-8f550000094d');
	define('MS_GROUP', 'https://api.moysklad.ru/api/remap/1.2/entity/group/dd4ce7fe-4f86-11e6-7a69-971100000043');
	define('MS_GROUP_ID', 'dd4ce7fe-4f86-11e6-7a69-971100000043');
	// projects
	define('MS_PROJECT_OZON', 'https://api.moysklad.ru/api/remap/1.2/entity/project/87950c19-4569-11eb-0a80-02c20026f2c0');
	define('MS_PROJECT_OZON_ID', '87950c19-4569-11eb-0a80-02c20026f2c0');
	define('MS_PROJECT_ALI', 'https://api.moysklad.ru/api/remap/1.2/entity/project/29c4043f-e916-11ea-0a80-09e0000818ad');
	define('MS_PROJECT_ALI_ID', '29c4043f-e916-11ea-0a80-09e0000818ad');
	define('MS_PROJECT_GOODS', 'https://api.moysklad.ru/api/remap/1.2/entity/project/8e9b955a-4569-11eb-0a80-044c002796a7');
	define('MS_PROJECT_GOODS_ID', '8e9b955a-4569-11eb-0a80-044c002796a7');
	define('MS_PROJECT_SBMMDSM_ID', 'f124f5f9-81e1-11ec-0a80-09a400364dfd');
	define('MS_PROJECT_4CLEANING', 'https://api.moysklad.ru/api/remap/1.2/entity/project/afe13e07-4569-11eb-0a80-07c500284a63');
	define('MS_PROJECT_4CLEANING_ID', 'afe13e07-4569-11eb-0a80-07c500284a63');
	define('MS_PROJECT_10KIDS', 'https://api.moysklad.ru/api/remap/1.2/entity/project/bc65476e-4569-11eb-0a80-07c500284a9e');
	define('MS_PROJECT_10KIDS_ID', 'bc65476e-4569-11eb-0a80-07c500284a9e');
	define('MS_PROJECT_MARKET', 'https://api.moysklad.ru/api/remap/1.2/entity/project/11341fbf-e358-11e8-9ff4-34e80008beae');
	define('MS_PROJECT_MARKET_ID', '11341fbf-e358-11e8-9ff4-34e80008beae');
	define('MS_PROJECT_MSKOREA', 'https://api.moysklad.ru/api/remap/1.2/entity/project/c336f827-4569-11eb-0a80-011800275208');
	define('MS_PROJECT_MSKOREA_ID', 'c336f827-4569-11eb-0a80-011800275208');
	define('MS_PROJECT_AVITO', 	'https://api.moysklad.ru/api/remap/1.2/entity/project/89e20949-d1d7-11ea-0a80-04a5000136db');
	define('MS_PROJECT_WB', 'https://api.moysklad.ru/api/remap/1.2/entity/project/336bfbae-6be6-11eb-0a80-06e50012e0e8');
	define('MS_PROJECT_WB_ID', '336bfbae-6be6-11eb-0a80-06e50012e0e8');
	define('MS_PROJECT_2HRS', 'https://api.moysklad.ru/api/remap/1.2/entity/project/daacfa18-8296-11eb-0a80-04700000b57e');
	define('MS_PROJECT_2HRS_ID', 'daacfa18-8296-11eb-0a80-04700000b57e');
	define('MS_PROJECT_YANDEX_DBS', 'https://api.moysklad.ru/api/remap/1.2/entity/project/4cdd140b-c4a0-11eb-0a80-03a300010ea3');
	define('MS_PROJECT_YANDEX_DBS_ID', '4cdd140b-c4a0-11eb-0a80-03a300010ea3');
	define('MS_PROJECT_YANDEX_ULLO', 'https://api.moysklad.ru/api/remap/1.2/entity/project/7dff5ef0-6676-11ec-0a80-0da10071ac31');
	define('MS_PROJECT_YANDEX_ULLO_ID', '7dff5ef0-6676-11ec-0a80-0da10071ac31');
	define('MS_PROJECT_YANDEX_SUMMIT', 'https://api.moysklad.ru/api/remap/1.2/entity/project/5a4a2924-fb2f-11ee-0a80-02a30045428e');
	define('MS_PROJECT_YANDEX_SUMMIT_ID', '5a4a2924-fb2f-11ee-0a80-02a30045428e');
	define('MS_PROJECT_YANDEX_VYSOTA', 'https://api.moysklad.ru/api/remap/1.2/entity/project/8f21cb11-29ea-11ef-0a80-170d00024b18');
	define('MS_PROJECT_YANDEX_VYSOTA_ID', '8f21cb11-29ea-11ef-0a80-170d00024b18');
	define('MS_PROJECT_OZON_DBS', 'https://api.moysklad.ru/api/remap/1.2/entity/project/f8f41543-0d6f-11ec-0a80-06930018cdb4'); // OZON KAORI DBS
	define('MS_PROJECT_OZON_DBS_ID', 'f8f41543-0d6f-11ec-0a80-06930018cdb4'); // OZON KAORI DBS
	define('MS_PROJECT_OZON_ULLO_DBS_ID', 'ab7e3e23-5500-11ec-0a80-08d1001f21d2'); // OZON ЮЛЛО DBS
	define('MS_PROJECT_XWAY_ID', '7b257d92-f4f8-11eb-0a80-0242001967f6'); // XWAY
	define('MS_PROJECT_SBMM_DSM_ID', 'f124f5f9-81e1-11ec-0a80-09a400364dfd'); // сбермегамаркет ДСМ
	define('MS_PROJECT_SBMM_ULLO_ID', 'b005506d-dc93-11ed-0a80-0d1a00257d67');
	define('MS_PROJECT_14DAYS_ALIANS', 'https://api.moysklad.ru/api/remap/1.2/entity/project/f6806943-e295-11ed-0a80-0c7e003397de');
	define('MS_PROJECT_14DAYS_ALIANS_ID', 'f6806943-e295-11ed-0a80-0c7e003397de');
	define('MS_PROJECT_SBMM_AST1', 'https://api.moysklad.ru/api/remap/1.2/entity/project/cf6b28f2-ddd1-11ee-0a80-14060024f47c');
	define('MS_PROJECT_SBMM_AST1_ID', 'cf6b28f2-ddd1-11ee-0a80-14060024f47c');
	define('MS_PROJECT_SBMM_AST2', 'https://api.moysklad.ru/api/remap/1.2/entity/project/c146207b-e66e-11ee-0a80-0be70003c3ab');
	define('MS_PROJECT_SBMM_AST2_ID', 'c146207b-e66e-11ee-0a80-0be70003c3ab');
	define('MS_PROJECT_SBMM_AST3', 'https://api.moysklad.ru/api/remap/1.2/entity/project/bef5b488-e92b-11ee-0a80-01f80021e14f');
	define('MS_PROJECT_SBMM_AST3_ID', 'bef5b488-e92b-11ee-0a80-01f80021e14f');
	define('MS_PROJECT_SBMM_AST4', 'https://api.moysklad.ru/api/remap/1.2/entity/project/dd2364c5-0b13-11ef-0a80-1731002e4fa4');
	define('MS_PROJECT_SBMM_AST4_ID', 'dd2364c5-0b13-11ef-0a80-1731002e4fa4');
	define('MS_PROJECT_SBMM_AST5', 'https://api.moysklad.ru/api/remap/1.2/entity/project/4216ef3e-1c13-11ef-0a80-08aa003df3c1');
	define('MS_PROJECT_SBMM_AST5_ID', '4216ef3e-1c13-11ef-0a80-08aa003df3c1');
	define('MS_PROJECT_SBMM_AST6', 'https://api.moysklad.ru/api/remap/1.2/entity/project/45e41546-1d40-11ef-0a80-0b0a0002542f');
	define('MS_PROJECT_SBMM_AST6_ID', '45e41546-1d40-11ef-0a80-0b0a0002542f');
	define('MS_PROJECT_YANDEX_KOSMOS', 'https://api.moysklad.ru/api/remap/1.2/entity/project/d2085d76-874e-11ef-0a80-070300359321');
	define('MS_PROJECT_YANDEX_KOSMOS_ID', 'd2085d76-874e-11ef-0a80-070300359321');
	define('MS_PROJECT_WB_ULLO', 'https://api.moysklad.ru/api/remap/1.2/entity/project/f50eda7d-d691-11ef-0a80-068e00252db9');
	define('MS_PROJECT_WB_ULLO_ID', 'f50eda7d-d691-11ef-0a80-068e00252db9');
	define('MS_PROJECT_WB_CCD', 'https://api.moysklad.ru/api/remap/1.2/entity/project/cba9f0d6-ed89-11ef-0a80-0ee10001da42');
	define('MS_PROJECT_WB_CCD_ID', 'cba9f0d6-ed89-11ef-0a80-0ee10001da42');
	
	//Ozon
	define('OZON_TESTMODE', false);
	define('OZON_CLIENT_ID', OZON_TESTMODE ? '836' : '45044');
	define('OZON_API_KEY', OZON_TESTMODE ? '0296d4f2-70a1-4c09-b507-904fd05567b9' : 'f5c08ee1-cd17-4609-8c16-d4252f1db88b');
	define('OZON_CLIENT_ID_KAORI', OZON_TESTMODE ? '836' : '72417');
	define('OZON_API_KEY_KAORI', OZON_TESTMODE ? '0296d4f2-70a1-4c09-b507-904fd05567b9' : 'ce466452-81e7-4b12-96ab-1cd00f682667');
	define('OZON_MAINURL', OZON_TESTMODE ? 'https://cb-api.ozonru.me/' : 'https://api-seller.ozon.ru/');
	define('OZON_API_VERSION', 'v2/');
	define('OZON_API_V1', 'v1/');
	define('OZON_API_V2', 'v2/');
	define('OZON_API_V3', 'v3/');
	define('OZON_API_V4', 'v4/');
	define('OZON_API_V5', 'v5/');
	define('OZON_API_PACKAGE_LABEL', 'posting/fbs/package-label');
	define('OZON_API_ORDERS_LIST', 'posting/fbs/list');
	define('OZON_API_ORDER_GET', 'posting/fbs/get');
	define('OZON_API_DELIVERING_STATUS', 'fbs/posting/delivering');
	define('OZON_API_LASTMILE_STATUS', 'fbs/posting/last-mile');
	define('OZON_API_DELIVERED_STATUS', 'fbs/posting/delivered');
	define('OZON_API_CANCELLED_STATUS', 'posting/fbs/cancel');
	define('OZON_API_EXEMPLAR_SET', 'fbs/posting/product/exemplar/set');
	define('OZON_API_EXEMPLAR_STATUS', 'fbs/posting/product/exemplar/status');
	define('OZON_WEARHOUSE1_ID', 19122245607000);
	define('OZON_WEARHOUSE2_ID', 22081289820000); // 2
	define('OZON_WEARHOUSE3_ID', 22426421413000); // Каори 1
	define('OZON_ULLO_WEARHOUSE1_ID', 22426421413000); // Склад Юлло ДБС
	define('OZON_ULLO_WEARHOUSE_MAIN', 17950354903000); // Склад Юлло Основной
	
	//yandex
	define('BERU_API_BASE_URL', 'https://api.partner.market.yandex.ru/');
	define('BERU_API_VERSION', 'v2/');
	define('BERU_API_CAMPAIGNS', 'campaigns/');
	define('BERU_API_BUSINESSES', 'businesses/');
	define('BERU_API_ULLOZZA_CAMPAIGN', '21632670');
	define('BERU_API_SUMMIT_CAMPAIGN', '93179396');
	define('BERU_API_SUMMIT_BUSINESS_ID', '119901806');
	define('BERU_API_VYSOTA_CAMPAIGN', '105464368');
	define('BERU_API_VYSOTA_BUSINESS_ID', '139441748');
	define('BERU_API_ABCASIA_CAMPAIGN', '21587057');
	define('BERU_API_ARUBA_CAMPAIGN', '21994237');
	define('BERU_API_10KIDS_CAMPAIGN', '22064982');
	define('BERU_API_MARKET4CLEANING_CAMPAIGN', '22113023');
	define('BERU_API_ALIANS_CAMPAIGN', '59391139');
	define('BERU_API_KOSMOS_CAMPAIGN', '126833621');
	define('BERU_API_KOSMOS_BUSINESS', '168153950');
	define('BERU_API_KOSMOS_WAREHOUSE', '1544731');
	define('BERU_API_SUMMIT_WAREHOUSE', 1383506);
	define('BERU_API_VYSOTA_WAREHOUSE', 1445466);
	define('BERU_API_ORDERS', 'orders');
	define('BERU_API_OFFER_MAPPING_ENTRIES', 'offer-mapping-entries');
	define('BERU_API_OFFER_MAPPINGS', 'offer-mappings');
	define('BERU_API_BOXES', 'boxes');
	define('BERU_API_SHIPMENTS', 'delivery/shipments/');
	define('BERU_API_STATUS', 'status');
	define('BERU_API_LABELS', 'delivery/labels/data');
	define('BERU_API_LABELS2', 'delivery/labels');
	define('BERU_API_STOCKS', 'offers/stocks');
	define('BERU_API_PRICES', 'offer-prices/updates');
	
	define('BERU_10KIDS_OUTLET', '315186571');
	
	//Yandex
	define('YA_FEEDBACKURL', 'https://api.partner.market.yandex.ru/v2/campaigns/');

	//Wildberries
	define('WB_STOCKS', 'https://wbxgate.wildberries.ru/stocks');
	define('WB_ORDERS', 'https://suppliers-orders.wildberries.ru/api/v1/orders');
	define('WB_API_ORDERS_NEW', 'api/v3/orders/new');
	define('WB_API_SUPPLIES', 'api/v3/supplies');
	define('WB_API_BASE_URL', 'https://suppliers-api.wildberries.ru/');
	define('WB_API_MARKETPLACE_API', 'https://marketplace-api.wildberries.ru/');
	define('WB_API_CONTENT_API', 'https://content-api.wildberries.ru/');
	define('WB_API_PRICES_API', 'https://discounts-prices-api.wildberries.ru/');
	define('WB_API_CARDS_LIST', 'content/v2/get/cards/list');
	define('WB_API_PRICES', 'api/v2/upload/task');
	define('WB_API_STOCKS', 'api/v3/stocks');
	define('WB_API_DISCOUNTS', 'public/api/v1/updateDiscounts');
	define('WB_API_ORDERS', 'api/v3/orders');
	define('WB_API_STICKERS', 'stickers?type=png&width=58&height=40');
	
	define('WB_WAREHOUSE', 3299);
	define('WB_WAREHOUSE_KOSMOS', 1073256);
	define('WB_WAREHOUSE_ULLO', 1210720);
	
	//sbermegamarket
	define('SBMM_TESTMODE', false);
	define('SBMM_SHOP', '4824');
	define('SBMM_SHOP_ULLOZZA', '15052');
	define('SBMM_SHOP_AST1', '129040');
	define('SBMM_SHOP_AST2', '129610');
	define('SBMM_SHOP_AST3', '136309');
	define('SBMM_SHOP_AST4', '136830');
	define('SBMM_SHOP_AST5', '156528');
	define('SBMM_SHOP_AST6', '157599');
	define('SBMM_SHOP_DSM', SBMM_TESTMODE ? '7045' : '18811');
	define('SBMM_API_BASE_URL', SBMM_TESTMODE ? 'https://partner.goodsteam.tech/api/' : 'https://partner.sbermegamarket.ru/api/');
	define('SBMM_API_MARKET',  'market/');
	define('SBMM_API_MERCHANTINTEGRATION',  'merchantIntegration/');
	define('SBMM_API_VERSION_V1', 'v1/');
	define('SBMM_API_ORDERS_SEARCH', 'orderService/order/search');
	define('SBMM_API_ORDERS_GET', 'orderService/order/get');
	define('SBMM_API_ORDERS_PACKING', 'orderService/order/packing');
	define('SBMM_API_ORDERS_CONFIRM', 'orderService/order/confirm');
	define('SBMM_API_ORDERS_REJECT', 'orderService/order/reject');
	define('SBMM_API_ORDERS_CANCELRESULT', 'orderService/order/cancelResult');
	define('SBMM_API_ORDERS_CLOSE', 'orderService/order/close');
	define('SBMM_API_PRICE_SAVE', 'offerService/manualPrice/save');
	define('SBMM_API_STOCK_UPDATE', 'offerService/stock/update');
	
	// DB
	define('DB_HOSTNAME', 'localhost');
	define('DB_USERNAME', 'u1003281_default');
	define('DB_PASSWORD', 'L8t5O7k0');
	define('DB_DATABASE', 'u1003281_stock');
?>