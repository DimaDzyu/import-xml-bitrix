<?php
define('ADMIN_MODULE_NAME', 'loadxml');
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php';
IncludeModuleLangFile(__FILE__);

//Здесь - какой-то системный код, читающие данные и всё такое

$APPLICATION->SetTitle(GetMessage("LOADXML_TITLE_IMPORT"));
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php';
?>

<form action="<?=$_SERVER['REQUEST_URI']?>" method="post" enctype="multipart/form-data">
	<br/>
	<p style="font-size: 17px;margin-bottom: 5px;margin-top: 0px;">Выберете файл и нажмите кнопку "Импортировать"</p>
	<br/>

	<div style="margin-bottom: 10px;">
		<input id="update_price" type="checkbox" name="update_price" style="float: left;margin-top: 1px;margin-right: 8px;cursor: pointer;" />
		<label for="update_price" style="cursor: pointer;">Обновить цены</label>
	</div>

	<div style="margin-bottom: 10px;">
		<input id="update_quantity" type="checkbox" name="update_quantity" style="float: left;margin-top: 1px;margin-right: 8px;cursor: pointer;" />
		<label for="update_quantity" style="cursor: pointer;">Обновить количество</label>
	</div>
	
	<input id="import_file" type="file" name="import_file" value="" style="display:none;">
	<input id="text_file" type="text" name="text_file" value="" />
	<input id="button_file" type="button" value="Выбрать файл" />
	<p style="margin-top: 8px;">Максимальный размер загружаемого файла 20Мб</p>
	<br/>
	<input id="submit_ajax" type="button" name="add_goods" class="adm-btn-save" value="Импортировать" />
	<img style="display:none;" id="load_send_providers" src="/upload/img/wait.gif">
</form>

<div id="block_action" style="display:none;margin-top: 30px;border-top: 1px solid #ccc;padding-top: 7px;">

	<p id="error_block" style="display:none;color: red;"></p>

	<p>Статус: <span id="status_action" style="color:green;font-style:italic;">Обработка</span></p>

	<p>Обработано товаров: <span id="processed_goods">0</span> шт.</p>

	<p>Добавлено новых секций: <span id="new_section_coll">0</span></p>
	<p>Название новых секций: <span id="new_section_name"></span></p>

	<p>Добавлено новых товаров: <span id="new_tov_coll">0</span></p>
	<p>Название новых товаров: <span id="new_tov_name"></span></p>

	<p>Товары у которых было обновлено количество: <span id="update_quantity_coll">0</span> шт.</p>
	<p>Товары у которых было обновлена цена: <span id="update_price_coll">0</span> шт.</p>

	<p>Товары которых нет у поставщика: <span id="item_no_provider_name"></span></p>

	<p style="display:none;">Время выполнения скрипта: <span id="time_work">0</span> сек.</p>
</div>

<script src="/lib/jquery/jquery-3.1.0.min.js"></script>
<script type="text/javascript">

$( document ).ready(function() {

	$("#button_file").click(function(){
		$( "#import_file" ).trigger( "click" );
	});	
	document.querySelector('#import_file').onchange = function(e)
	{
		files = this.files;
		for(var a=0;a<files.length;a++)
		{
			$( "#text_file" ).val( files[a].name);
		}

		if(files.length == 0)
		{
			$( "#text_file" ).val('');
		}
	}

	function AjaxAction(update_price, update_quantity, count_section, count_element, number, time, update_quantity_coll, update_price_coll)
	{
	    var $input = $("#import_file");
	    var data = new FormData;

	    data.append('files', $input.prop('files')[0]);
	    data.append( 'update_price', update_price );
	    data.append( 'update_quantity', update_quantity );
	    data.append( 'time', time );

	    data.append( 'number', number );
	    data.append( 'count_section', count_section );
	    data.append( 'count_element', count_element );
	    data.append( 'update_quantity_coll', update_quantity_coll );
	    data.append( 'update_price_coll', update_price_coll );

		$.ajax({
	        url: '/bitrix/admin/ajax_loadxml.php',
	        type: 'POST',
			data: data,
	        cache: false,
	        dataType: 'json',
	        processData: false,
	        contentType: false,
	        success: function( respond )
	        {
	            console.log(respond);

	            //error
	            if(respond.error_section!='' || respond.error_element!='')
	            {
	            	$('#error_block').append(respond.error_section);
	            	$('#error_block').append(respond.error_element);
	            	$('#error_block').show();
	            }

	            //processed_goods
	            $('#processed_goods').html(respond.number+' из '+respond.all_count);

	            //section
	            $('#new_section_coll').text(respond.count_section);
	            $('#new_section_name').append(respond.name_section);

	            //element
	            $('#new_tov_coll').text(respond.count_element);
	            $('#new_tov_name').append(respond.name_element);

	            //update_quantity update_price
	            $('#update_quantity_coll').text(respond.update_quantity_coll);
	            $('#update_price_coll').text(respond.update_price_coll);

	            //time
	            $('#time_work').html(respond.time);

	            //Show action block
	            $('#block_action').show();

	            //Recursion
	            if(respond.finish != 'true')
	            {
	            	AjaxAction(update_price, update_quantity, respond.count_section, respond.count_element, respond.number, respond.time, respond.update_quantity_coll, respond.update_price_coll);
	            }
	            else
	            {
            		var action = 'no_provider';
            		$("#status_action").text('Обработка товаров которых нет у поставщика');

            		AjaxActionNoProvider(action);
	            }
	        }
	    });
	}

	//AjaxActionNoProvider
	function AjaxActionNoProvider(action)
	{
	    var $input = $("#import_file");
	    var data = new FormData;

	    data.append('files', $input.prop('files')[0]);
	    data.append( 'action', action);

	    $.ajax({
	        url: '/bitrix/admin/ajax_loadxml.php',
	        type: 'POST',
			data: data,
	        cache: false,
	        dataType: 'json',
	        processData: false,
	        contentType: false,
	        success: function( respond )
	        {
	        	console.log(respond);

   				if(respond.finish_no_provider != 'true')
            	{
            		var action = 'no_provider';

            		//item_no_provider_name
	           		$('#item_no_provider_name').append(respond.item_no_provider_name);

            		AjaxActionNoProvider(action);
            	}
            	else
            	{
	            	$("#submit_ajax").show();
	   				$("#load_send_providers").hide();
	   				$("#status_action").text('Готово');
            	}
	        }

	    });
	}

	//Ajax form
	$("#submit_ajax").click(function()
	{
		if($('#text_file').val()=='')
		{
			$('#text_file').css('border-color','red');
		}
		else
		{
			$('#text_file').css('border-color','');

			var update_price = '';
			var update_quantity = '';
			var count_section = '0';
			var count_element = '0';
			var update_coll = '0';
			var update_price_coll = '0';
			var time = '0';
			var number = '0';

			if($('#update_price').is( ":checked" ))
		    {
		    	update_price = 'on';
		    }

		    if($('#update_quantity').is( ":checked" ))
		    {
		    	update_quantity = 'on';
		    }
			
		    $("#submit_ajax").hide();
		    $("#load_send_providers").show();
		    $('#block_action').hide();
		    $("#status_action").text('Обработка');
		    $('#error_block').html('');
		    $('#new_section_name').html('');
		    $('#new_tov_name').html('');
		    $('#item_no_provider_name').html('');
		    $('#processed_goods').html('0');
		    $('#new_section_coll').html('0');
		    $('#new_tov_coll').html('0');
		    $('#update_quantity_coll').html('0');
		    $('#update_price_coll').html('0');
		    $('#time_work').html('0');

			AjaxAction(update_price, update_quantity, count_section, count_element, number, time, update_quantity_coll, update_price_coll);
		}
	});	

});
</script>
<?
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php';
?>