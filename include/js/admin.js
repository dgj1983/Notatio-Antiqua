gpadmin={uploadFile:function(o){o.parentNode.className="active";for(var m=o.value.toString();pos=m.search("\\\\");){if(pos==-1)break;m=m.substr(pos+1)}$('<div><a href="javascript:void(0)" onclick="gpadmin.rmFile(this)" >Remove</a><div class="name">'+m+"</div></div>").appendTo(o.parentNode);$("#gp_upload_list .active").size()<5&&gpadmin.addFile()},addFile:function(){$("#gp_upload_field").clone().attr("id","").attr("class","upload_ready").appendTo("#gp_upload_list")},rmFile:function(o){$(o).closest(".active").remove();
$("#gp_upload_list .upload_ready").size()<1&&gpadmin.addFile()}};function GetCoords(o){var m,q=false,p={};p.h=o.outerHeight();if(o.hasClass("editable_area")){m=o.children().not(".gplinks");if(m.length==2){m=m.eq(1);q=m.offset();p.w=m.outerWidth()}}if(q===false){q=o.offset();p.w=o.outerWidth()}p.left=q.left;p.top=q.top;return p}
$(function(){function o(){function g(){var k,a,b,d,h,e;a=$(window);h=a.width();if(h>930)h=930;$(".panelwrapper").width(h);k=$(document).height();a=a.height();a=Math.max(a,k);$("#editable_bar").css("height",a);b=[];$(".editable_mark").each(function(s,t){d=$(t).data("cid");h=$("#EditableArea_"+d);if(h.length!=0){e=h.offset().top;for(e=Math.round(e/20)*20;b[e];)e+=20;b[e]=true;t.style.top=e+"px"}})}var n,l=0,j=0;n=$("<div/>").appendTo("body");$("a.ExtraEditLink").each(function(k,a){var b,d;$b=$(a);b=
v++;$b.closest(".editable_area").attr("id","EditableArea_"+b).data("cid",b);d=a.title.replace(/_/g," ");if(d.length>15)d=d.substr(0,14)+"...";b=$b.data("cid",b).clone(false).data("cid",b).attr("class","editable_mark").text(d).show().css({left:0,right:0}).appendTo(n).outerWidth();if(l<b)l=b;j++});if(!(j<1)){n.attr("id","editable_bar").css("width",1).show().animate({width:"7px"});$("#edit_list_new, #editable_bar").hover(function(){$("#editable_bar").stop(true).fadeTo("fast",1).animate({width:l})},function(){$("#editable_bar").stop(true).fadeTo("fast",
1).animate({width:"7px"},1300)});g();window.setInterval(g,5E3);$(window).resize(function(){g()})}}function m(){$(".editable_area").bind("mousemove",function(g){var n=$(this);n.parent().closest(".editable_area").length>0&&g.stopPropagation();q(n,1);$("#edit_area_overlay_cont div").stop(true,true).delay(1400).fadeOut(500)}).bind("mouseleave",function(){$("#edit_area_overlay_cont div").stop(true,true).delay(300).fadeOut(200)});$(".ExtraEditLink").bind("mouseenter",function(){$("#edit_area_overlay_cont div").stop(true,
true).fadeIn(1).show()}).bind("mouseleave",function(){$("#edit_area_overlay_cont div").stop(true,true).hide()});$(".editable_mark").hover(function(){var g=$(this).data("cid");c=$("#EditableArea_"+g);if(c.length!=0){g=$("#editable_bar");c.offset().left+c.width()-20<g.width()&&$("#editable_bar").fadeTo("slow",0.5);q(c)}},function(){$("#edit_area_overlay_cont div").hide();$("#editable_bar").fadeTo("fast",1)})}function q(g,n){var l,j,k,a,b;l=g.attr("id");a=GetCoords(g);var d=a.top,h=a.left;k=a.w;a=a.h;
b=$(window).scrollTop();j=l+":"+d+":"+n+":"+b;var e=$("#edit_area_overlay_cont");e.find("div").stop(true,true).show();var s=$("#edit_area_overlay_top"),t=$("#edit_area_overlay_bottom");if(j!=f){f=j;if(n){if(r!=l){e.find("a").remove();l=g.children(".ExtraEditLink").clone(true).show();k<80||a<30?t.append(l):s.append(l);if(k<l.outerWidth())k=l.outerWidth();b=b+$("#gpadminpanel").height()-d;b=Math.max(0,b);l.css({top:b})}}else e.find("a").remove();$(document).width();e.css({top:d-2,left:h-2});s.width(k+
2);t.css("top",a).width(k+2);$("#edit_area_overlay_right").css("left",k).height(a);$("#edit_area_overlay_left").height(a)}}function p(){$(".collapsible .head").click(function(){$(this).toggleClass("hidden").next().slideToggle();return false})}if(isadmin){var f=false,r=false,v=1,u=$("body");if(function(){var g=$("#gp_admin_html").prependTo(u);if(g.size()==0)return false;var n=$("#gpadminpanel").height();g.height(n);return true}()){(function(){function g(a){var b,d,h,e;h="cmd=panelposition";if(j.hasClass("docked")){d=
b=0;e="auto";h+="&paneldock=true"}else{b=Math.max(10,panelposx);d=Math.max(40,panelposy);e=j.outerHeight();h+="&panelposx="+b+"&panelposy="+d}j.css({left:b,top:d,height:e});a||gpPublic.postC(window.location.href,h)}function n(a){var b=[],d=typeof CKEDITOR!="undefined";if(d)for(i in CKEDITOR.instances)if(CKEDITOR.instances[i].mode=="wysiwyg"){CKEDITOR.instances[i].setMode("source");b.push(i)}if(a){j.addClass("docked").appendTo(k);k.css("height","auto")}else{k.css("height",j.height()+50);j.removeClass("docked").appendTo(u)}d&&
window.setTimeout(function(){for(i in b)CKEDITOR.instances[b[i]].setMode("wysiwyg")},100)}var l,j,k;l=$("#admincontent");if(!(l.length<1)){(function(){var a="";if(paneldock)a="docked";j=l.parent().wrap('<div id="admincontainer" class="'+a+'"></div>').parent();k=j.parent();if(paneldock){if(j.width()<680){a=k.offset();panelposx=a.left;panelposy=a.top;n(false);g(true)}}else{n(false);g(true)}})();$("a.docklink").live("click",function(a){a.preventDefault();n(!j.hasClass("docked"));g(false)});$("#admincontent_panel").live("mousedown",
function(a){if(a.target.nodeName=="DIV"){var b,d,h;a.preventDefault();b=a.clientX;d=a.clientY;$(document).bind("mousemove.admin",function(e){x=Math.max(10,e.clientX);y=Math.max(40,e.clientY);var s=h.position();h.css({top:s.top+(y-d),left:s.left+(x-b)});b=x;d=y;e.preventDefault()});a=j.offset();h=$('<div id="admin_drag_box"></div>').css({top:a.top-2,left:a.left-2,width:j.outerWidth()+4,height:j.outerHeight()+4}).appendTo(u);if(j.hasClass("docked")){n(false);panelposx=a.left;panelposy=a.top;g(false)}$(document).unbind("mouseup.admin").bind("mouseup.admin",
function(){if(h){var e=h.offset();panelposx=parseInt(e.left+2);panelposy=parseInt(e.top+2);g(false);$(document).unbind("mousemove.admin");h.remove();h=false}})}})}})();u.addClass("gpAdmin").trigger("AdminReady");window.setTimeout(function(){o();m();p()},500)}}});
function RenamePrep(o){function m(){p();$("input[disabled=disabled]").each(function(f,r){$(r).fadeTo(400,0.6)});$("input.title_label").bind("keyup change",p);$(".label_synchronize a").click(q)}function q(f){f.preventDefault();f=$(this).closest("td");var r=f.find("a:visible");f.find("a").show();r.hide();if(r=f.find("a:visible").get(0))if(r.className=="slug_edit"){f.find("input").addClass("sync_label").attr("disabled","disabled").fadeTo(400,0.6);p()}else f.find("input").removeClass("sync_label").attr("disabled",
"").fadeTo(400,1)}function p(){var f;f=$("input.title_label").val();f=f.replace(/</g," ").replace(/>/g," ").replace(/\//g," ").replace(/\\/g," ").replace(/\|/g," ");f=$.trim(f);f=f.replace(/"/g,"").replace(/'/g,"").replace(/\?/g,"").replace(/#/g,"").replace(/\*/g,"").replace(/:/g,"");f=f=f.replace(/[\x00-\x1F\x7F]/g,"");$("input.sync_label").val(f);return true}o?m():$(document).one("cbox_complete",function(){m()})};