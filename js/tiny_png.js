/*
Author: a2exfr
http://my-sitelab.com/
 */
 
$(document).ready(function() 
    { 
        $("#myTable").tablesorter({cssHeader:"gp_header",cssAsc:"gp_header_asc",cssDesc:"gp_header_desc"}); 
    
	$(".un_check").click(function(){
			$("#myTable").find('.compr').each(function(){ 
				 if ( !$(this).prop('checked')) {
					 $(this).prop( "checked", true );
					 
				 } else {
					  $(this).prop( "checked", false );
				 }
				
				
			})
		
		})
		
		$(".compress_selected").click(function(){
			image_urls = [];
			$("#myTable").find('.compr').each(function(){ 
				if ( $(this).prop('checked')) {	
					image_urls.push($(this).val());
				}
			
			})
			if(image_urls.length > 0)	{compress_selected();}
		
		})	
	
	
		$(".compress_one").click(function(){
			var file = $(this).parent().parent().find('.compr').val();
			loading();
			var href = jPrep(window.location.href) + '&cmd=compress_one' + '&file=' + file;
			$.getJSON(href, ajaxResponse);
					
		})
		
		
		
		
		function compress_selected() {
			loading();
			var data = {
				  cmd   : 'compress_selected',
				  my_value  : image_urls,
			};
		 $gp.postC(window.location.href, data);
		}
		
	 $gp.response.CompressRespond = function(arg) {
		loaded();
		console.log(arg.CONTENT);
       if (arg.CONTENT===true) {
		  window.alert("All files compressed!");
		 document.location.href = "/Admin_TinyPNG";
	   }
	   
	} 

	$gp.response.CompressOneRespond = function(arg) {
		loaded();
		console.log(arg.CONTENT);
       if (arg.CONTENT===true) {
		  window.alert("File compressed!");
		 document.location.href = "/Admin_TinyPNG";
	   }else {
		    window.alert(arg.CONTENT);
	   }
	   
	} 
	
	
	}


	
); 