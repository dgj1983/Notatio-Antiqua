$(function(){function d(a){var b=true;a.find("label.all_checkbox input").each(function(e,c){if(c.checked)$(c.parentNode).addClass("checked").removeClass("unchecked");else{b=false;$(c.parentNode).removeClass("checked").addClass("unchecked")}});b||a.find("input.select_all").attr("checked","")}$("input.select_all").click(function(){var a=this.checked,b=$(this).closest(".all_checkboxes");b.find("input[type=checkbox]").each(function(e,c){if(c.disabled)return true;c.checked=a?true:false});d(b)});$("label.all_checkbox").click(function(){var a=
$(this).closest(".all_checkboxes");window.setTimeout(function(){d(a)},50)});(function(){$("label.all_checkbox input").css({margin:0,padding:0,position:"absolute",left:-100,height:0,width:0});$(".all_checkboxes").each(function(a,b){d($(b))})})()});