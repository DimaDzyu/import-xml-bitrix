<?
define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", true);
define("NO_AGENT_CHECK", true);
define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

$data = array();
$start = time();

if(CModule::IncludeModule("iblock"))

if (CModule::IncludeModule("catalog"))

if($_POST['action'] == 'no_provider')
{
	if(!empty($_FILES['files']['tmp_name']))
	{
		$xml = simplexml_load_file($_FILES['files']['tmp_name'], "SimpleXMLElement", LIBXML_NOCDATA);

		//------Elements------//
		$arObElementsXml = $xml->products->product;
		$arElementsXml = array();

		$i=0;
		foreach ($arObElementsXml as $key => $value)
		{
			foreach ($value->attributes() as $key2 => $value2)
			{
				$arElementsXml[$i][strtoupper($key2)] = (string)trim($value2);
			}

			foreach ($value as $key2 => $value2)
			{
				$arElementsXml[$i][strtoupper($key2)] = (string)trim($value2);

				foreach ($value2 as $key3 => $value3)
				{
					foreach ($value3->attributes() as $key4 => $value4)
					{
						$arElementsXml[$i][strtoupper($key4)] = (string)trim($value4);
					}
				}
			}
			$i++;
		}

		//Get Elements
		$arSelect = Array("ID", "IBLOCK_ID", "NAME","PROPERTY_XML_CODE","PROPERTY_XML_PARENTUID", "PROPERTY_ITEM_NO_PROVIDER");

		$arFilter = Array("IBLOCK_ID"=>$IBLOCK_ID);
		$res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
		while($ob = $res->GetNextElement())
		{
			$arFields = $ob->GetFields();
			if(!empty($arFields['PROPERTY_XML_CODE_VALUE']))
			{
				$arElementIsset[$arFields['PROPERTY_XML_CODE_VALUE']] = $arFields;
			}
		}

		//This item is no provider ITEM_NO_PROVIDER
		foreach ($arElementsXml as $key => $value)
		{
			$arElementsXmlAgo[$value['CODE']] = $value;
		}
	
		$number_no_provider = 0;
		$num_pr = 0;
		$data['finish_no_provider'] = 'true';

		foreach ($arElementIsset as $key => $value)
		{
			if( !isset($arElementsXmlAgo[$key]) && $value['PROPERTY_ITEM_NO_PROVIDER_VALUE'] != "Y")
			{
				//NUMBER
				if($num_pr>=200){break;}

				$el = new CIBlockElement;
				$arLoadProductArray = Array("ACTIVE"=>"N");
				$res = $el->Update($value['ID'], $arLoadProductArray);
				CIBlockElement::SetPropertyValueCode($value['ID'], '134', '32');

				$data['finish_no_provider'] = 'false';
				$num_pr++;

				$data['item_no_provider_name'] .= $value['NAME'].', ';
				$ItemNoProvider[] = $value['NAME'];
			}
		}

		$data['arElementsXmlAgo'] = $arElementsXmlAgo;
		$data['arElementIsset'] = $arElementIsset;
		$data['ItemNoProvider'] = $ItemNoProvider;
		$data['CountItemNoProvider'] = count($ItemNoProvider);

	}
}
else
{
	if(!empty($_FILES['files']['tmp_name']))
	{
		$xml = simplexml_load_file($_FILES['files']['tmp_name'], "SimpleXMLElement", LIBXML_NOCDATA);

		//------Sections------//
		$arObSectionsXml = $xml->productgroups->productgroup;
		$arSectionsXml = array();
		$IBLOCK_ID = 19;

		//Set not processed section.
		$rsSect = CIBlockSection::GetList(array('id' => 'asc'), array('IBLOCK_ID' => $IBLOCK_ID, 'CODE' => 'not_processed_goods'),false, array("ID","IBLOCK_SECTION_ID","CODE","NAME"));
		while($arSect = $rsSect->Fetch())
		{
		   $arSestionNotProcessedt = $arSect;
		}

		if(!empty($arSestionNotProcessedt))
		{
			$SestionNotProcessedt = $arSestionNotProcessedt['ID'];
		}
		else
		{
			$bs = new CIBlockSection;
			$arFields = Array(
			  "ACTIVE" => 'N',
			  "IBLOCK_ID" => $IBLOCK_ID,
			  "NAME" => 'Не обработанные товары',
			  "CODE" => 'not_processed_goods'
			);

			$SestionNotProcessedt = $bs->Add($arFields, false);
		}

		//Get Sections
		$rsSect = CIBlockSection::GetList(array('id' => 'asc'), array('IBLOCK_ID' => $IBLOCK_ID),false, array("ID","IBLOCK_SECTION_ID","NAME","UF_*"));
		while($arSect = $rsSect->Fetch())
		{
		   $arSestionIsset[$arSect['UF_IMPORT_ID']] = $arSect;
		}

		//Formation of the main body
		foreach ($arObSectionsXml as $key => $value)
		{
			$uid = (string)trim($value->attributes()->UID);
			$parentUID = (string)trim($value->attributes()->parentUID);
			$name =  (string)trim($value->name);

			//Only new sections add
			if(!isset($arSestionIsset[$uid]))
			{
				$arSectionsXml[$uid] = array(
					'ID' => $uid,
					'PARENT_ID' => $parentUID,
					'NAME' => $name,
					'BITRIX_ID' => ''
				);
			}
		}

		$arNewSectionsXml = array();
		$arEmptyParentId = array();
		$arErrorSectionAdd = array();

		foreach ($arSectionsXml as $key2 => $value2)
		{
			if($value2['PARENT_ID'] == '00000000-0000-0000-0000-000000000000')
			{
				$IBLOCK_SECTION_ID = $SestionNotProcessedt;
			}
			else
			{
				$IBLOCK_SECTION_ID = $arNewSectionsXml[$value2['PARENT_ID']]['BITRIX_ID'];

				if(empty($IBLOCK_SECTION_ID))
				{
					//Find for existing array
					$IBLOCK_SECTION_ID = $arSestionIsset[$value2['PARENT_ID']]['ID'];

					if(empty($IBLOCK_SECTION_ID))
					{
						$IBLOCK_SECTION_ID = '';
						$arEmptyParentId[] = $value2['ID'];
					}
				}
			}

			$bs = new CIBlockSection;
			$arFields = Array(
			  "ACTIVE" => 'N',
			  "IBLOCK_SECTION_ID" => $IBLOCK_SECTION_ID,
			  "IBLOCK_ID" => $IBLOCK_ID,
			  "NAME" => $value2['NAME'],
			  "UF_IMPORT_ID" => $value2['ID'],
			  "UF_IMPORT_PARENT_ID" => $value2['PARENT_ID'],
			);

			$ID = $bs->Add($arFields, false);

			if($ID>0)
			{
				$arNewSectionsXml[$value2['ID']] = $value2;
				$arNewSectionsXml[$value2['ID']]['BITRIX_ID'] = $ID;
			}
			else
			{
				$arErrorSectionAdd[$value2['ID']] = $value2;
				$arErrorSectionAdd[$value2['ID']]['TEXT_ERROR'] = $bs->LAST_ERROR;
			}
		}

		//ReSort section on info-block
		CIBlockSection::ReSort($IBLOCK_ID);

		//If it has not previously been found ParentId
		if(count($arEmptyParentId)>0)
		{
			foreach ($arEmptyParentId as $key => $value)
			{
				$arEmptyAfter = $arNewSectionsXml[$value];
				$ID = $arEmptyAfter['BITRIX_ID'];
				$IBLOCK_SECTION_ID = $arNewSectionsXml[$arEmptyAfter['PARENT_ID']]['BITRIX_ID'];

				$bs = new CIBlockSection;
				$arFields = Array(
				  "IBLOCK_SECTION_ID" => $IBLOCK_SECTION_ID
				);

				$bs->Update($ID, $arFields);
			}
		}

		//------Elements------//
		$arObElementsXml = $xml->products->product;
		$arElementsXml = array();

		$i=0;
		foreach ($arObElementsXml as $key => $value)
		{
			foreach ($value->attributes() as $key2 => $value2)
			{
				$arElementsXml[$i][strtoupper($key2)] = (string)trim($value2);
			}

			foreach ($value as $key2 => $value2)
			{
				$arElementsXml[$i][strtoupper($key2)] = (string)trim($value2);

				foreach ($value2 as $key3 => $value3)
				{
					foreach ($value3->attributes() as $key4 => $value4)
					{
						$arElementsXml[$i][strtoupper($key4)] = (string)trim($value4);
					}
				}
			}
			$i++;
		}

		//Get Elements
		$arSelect = Array("ID", "IBLOCK_ID", "NAME","PROPERTY_XML_CODE","PROPERTY_XML_PARENTUID", "PROPERTY_ITEM_NO_PROVIDER");

		$arFilter = Array("IBLOCK_ID"=>$IBLOCK_ID);
		$res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
		while($ob = $res->GetNextElement())
		{
			$arFields = $ob->GetFields();
			if(!empty($arFields['PROPERTY_XML_CODE_VALUE']))
			{
				$arElementIsset[$arFields['PROPERTY_XML_CODE_VALUE']] = $arFields;
			}
		}

		//Get Sections
		unset($arSestionIsset);
		$rsSect = CIBlockSection::GetList(array('id' => 'asc'), array('IBLOCK_ID' => $IBLOCK_ID),false, array("ID","IBLOCK_SECTION_ID","NAME","UF_*"));
		while($arSect = $rsSect->Fetch())
		{
		   $arSestionIsset[$arSect['UF_IMPORT_ID']] = $arSect;
		}

		$i=0;
		$number = $_POST['number'];
		foreach ($arElementsXml as $key => $value)
		{
			if($number > 0)
			{
				$number--;
				continue;
			}
			//NUMBER
			if($i>=250){break;}
			$PRODUCT_ID = '';

			if(!isset($arElementIsset[$value['CODE']]))
			{
				$el = new CIBlockElement;

				$PROP = array();
				$PROP[120] = $value['CODE'];
				$PROP[121] = $value['CODE1C'];
				$PROP[122] = $value['PARENTUID'];
				$PROP[97] = $value['CODE'];
				$PROP[127] = 886;

				$arLoadProductArray = Array(
				  "MODIFIED_BY"    => $USER->GetID(),
				  "IBLOCK_SECTION_ID" => $arSestionIsset[$value['PARENTUID']]['ID'],
				  "IBLOCK_ID"      => $IBLOCK_ID,
				  "PROPERTY_VALUES"=> $PROP,
				  "NAME"           => $value['NAME'],
				  "ACTIVE"         => "N",
				  "PREVIEW_TEXT"   => $value['BRIEF-DESCRIPTION'],
				  "DETAIL_TEXT"    => $value['DESCRIPTION'],
				  "DETAIL_PICTURE" => CFile::MakeFileArray($value['ENLARGED'])
				  );

				if($PRODUCT_ID = $el->Add($arLoadProductArray))
				{

					//Add quantity
					$arFields = array(
		                  "ID" => $PRODUCT_ID, 
		                  "QUANTITY" => $value['IN-STOCK']
		                  );
					CCatalogProduct::Add($arFields);

					//Add price
					$PRICE_TYPE_ID = 1;
					$arFields = Array(
					    "PRODUCT_ID" => $PRODUCT_ID,
					    "CATALOG_GROUP_ID" => $PRICE_TYPE_ID,
					    "PRICE" => $value['PRICE'],
					    "CURRENCY" => "RUB"
					);

					$res = CPrice::GetList(
					        array(),
					        array(
					                "PRODUCT_ID" => $PRODUCT_ID,
					                "CATALOG_GROUP_ID" => $PRICE_TYPE_ID
					            )
					    );

					if ($arr = $res->Fetch())
					{
					    CPrice::Update($arr["ID"], $arFields);
					}
					else
					{
					    CPrice::Add($arFields);
					}

					
				  $arNewEl[$value['CODE']]['NAME'] = $value['NAME'];
				  $arNewEl[$value['CODE']]['BITRIX_ID'] = $PRODUCT_ID;
				}
				else
				{
				  $arNewElError[$value['CODE']]['NAME'] = $value['NAME'];
				  $arNewElError[$value['CODE']]['TEXT_ERROR'] = $el->LAST_ERROR;
				}
			}
			elseif(isset($arElementIsset[$value['CODE']]) && ($_POST['update_quantity'] == 'on' || $_POST['update_price'] == 'on'))
			{
				$PRODUCT_ID = $arElementIsset[$value['CODE']]['ID'];

				if($_POST['update_quantity'] == 'on' )
				{
					//Add quantity
					$arFields = array(
		                  "ID" => $PRODUCT_ID, 
		                  "QUANTITY" => $value['IN-STOCK']
		                  );
					CCatalogProduct::Add($arFields);

					$UpdateQuantity[] = $PRODUCT_ID;
				}
				
				if($_POST['update_price'] == 'on' )
				{
					//Add price
					$PRICE_TYPE_ID = 1;
					$arFields = Array(
					    "PRODUCT_ID" => $PRODUCT_ID,
					    "CATALOG_GROUP_ID" => $PRICE_TYPE_ID,
					    "PRICE" => $value['PRICE'],
					    "CURRENCY" => "RUB"
					);

					$res = CPrice::GetList(
					        array(),
					        array(
					                "PRODUCT_ID" => $PRODUCT_ID,
					                "CATALOG_GROUP_ID" => $PRICE_TYPE_ID
					            )
					    );

					if ($arr = $res->Fetch())
					{
					    CPrice::Update($arr["ID"], $arFields);
					}
					else
					{
					    CPrice::Add($arFields);
					}

					$UpdatePrice[] = $PRODUCT_ID;
				}
			}

			$i++;
		}

		$data['number'] = $i+$_POST['number'];
		$data['all_count'] = count($arElementsXml);

		if($data['number'] >= $data['all_count'])
		{
			$data['finish'] = 'true';
		}
	}

	/*
	*Result
	*/
		//Section
		$data['error_section'] = '';
		if(count($arErrorSectionAdd)>0)
		{
			foreach($arErrorSectionAdd as $key => $value)
			{
				$data['error_section'] .= '('.$value['NAME'].') '.$value['TEXT_ERROR'];
			}
		}

		$data['count_section'] = count($arNewSectionsXml) + $_POST['count_section'];
		if(count($arNewSectionsXml)>0)
		{
			$i=1;
			foreach($arNewSectionsXml as $key => $value)
			{
				$data['name_section'] .= $value['NAME'];
				if(count($arNewSectionsXml)>$i)
				{
					$data['name_section'] .= ", ";
				}			
				$i++;
			}
		}

		//Element
		if(count($arNewElError)>0)
		{
			foreach ($arNewElError as $key => $value)
			{
				$data['error_element'] .= '('.$value['NAME'].') '.$value['TEXT_ERROR'];
			}
		}

		
		$data['count_element'] = count($arNewEl) + $_POST['count_element'];
		if(count($arNewEl)>0)
		{	
			foreach($arNewEl as $key => $value)
			{
				$data['name_element'] .= $value['NAME'];
				$data['name_element'] .= ", ";
			}
		}

		if($data['finish'] == 'true')
		{
			$data['name_element'] = substr($data['name_element'], 0, -2);
		}

		//Update UpdateQuantity UpdatePrice
		$data['update_quantity_coll'] = count($UpdateQuantity) + $_POST['update_quantity_coll'];
		$data['update_price_coll'] = count($UpdatePrice) + $_POST['update_price_coll'];
		
		
		//Time
		//sleep(2);
		$data['time'] = $_POST['time'] + (time() - $start);

	 	$data['_POST'] = $_POST;
	 	$data['_FILES'] = $_FILES;
}

echo json_encode( $data );
?>