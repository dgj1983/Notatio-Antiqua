(function(c,ea){function f(b,d){b=b?' id="'+m+b+'"':"";d=d?' style="'+d+'"':"";return c("<div"+b+d+"/>")}function q(b,d){d=d==="x"?o.width():o.height();return typeof b==="string"?Math.round(b.match(/%/)?d/100*parseInt(b,10):parseInt(b,10)):b}function N(b){b=c.isFunction(b)?b.call(i):b;return a.photo||b.match(/\.(gif|png|jpg|jpeg|bmp)(?:\?([^#]*))?(?:#(\.*))?$/i)}function aa(){for(var b in a)if(c.isFunction(a[b])&&b.substring(0,2)!=="on")a[b]=a[b].call(i);a.rel=a.rel||i.rel||"nofollow";a.href=a.href||
c(i).attr("href");a.title=a.title||i.title}var ba={transition:"elastic",speed:300,width:false,initialWidth:"600",innerWidth:false,maxWidth:false,height:false,initialHeight:"450",innerHeight:false,maxHeight:false,minWidth:0,minHeight:0,scalePhotos:true,scrolling:true,inline:false,html:false,iframe:false,photo:false,href:false,title:false,rel:false,opacity:0.9,preloading:true,current:"image {current} of {total}",previous:"previous",next:"next",close:"close",open:false,loop:true,slideshow:false,slideshowAuto:true,
slideshowSpeed:2500,slideshowStart:"start slideshow",slideshowStop:"stop slideshow",onOpen:false,onLoad:false,onComplete:false,onCleanup:false,onClosed:false,overlayClose:true,escKey:true,arrowKey:true},r="colorbox",m="cbox",fa=m+"_open",L=m+"_load",O=m+"_complete",P=m+"_cleanup",ca=m+"_closed",E=c.browser.msie&&!c.support.opacity,Q=E&&c.browser.version<7,M=m+"_IE6",u,l,A,s,R,S,T,U,j,o,n,H,v,V,w,I,J,W,B,C,x,y,i,X,h,a,t,F,e,K=m+"Element";e=c.fn[r]=c[r]=function(b,d){var g=this;if(!g[0]&&g.selector)return g;
b=b||{};if(d)b.onComplete=d;if(!g[0]||g.selector===undefined){g=c("<a/>");b.open=true}g.each(function(){c(this).data(r,c.extend({},c(this).data(r)||ba,b)).addClass(K)});b.open&&e.launch(g[0]);return g};e.launch=function(b){i=b;a=c.extend({},c(i).data(r));aa();if(a.rel!=="nofollow"){j=c("."+K).filter(function(){return(c(this).data(r).rel||this.rel)===a.rel});h=j.index(i);if(h===-1){j=j.add(i);h=j.length-1}}else{j=c(i);h=0}if(!t){t=F=true;X=i;try{X.blur()}catch(d){}c.event.trigger(fa);a.onOpen&&a.onOpen.call(i);
u.css({opacity:+a.opacity,cursor:a.overlayClose?"pointer":"auto"}).show();a.w=q(a.initialWidth,"x");a.h=q(a.initialHeight,"y");e.position(0);Q&&o.bind("resize."+M+" scroll."+M,function(){u.css({width:o.width(),height:o.height(),top:o.scrollTop(),left:o.scrollLeft()})}).trigger("scroll."+M)}V.add(J).add(I).add(w).add(v).hide();W.html(a.close).show();e.slideshow();e.load()};e.init=function(){o=c(ea);l=f().attr({id:r,"class":E?m+"IE":""});u=f("Overlay",Q?"position:absolute":"").hide();A=f("Wrapper");
$controls=f("Controls").append(V=f("Current"),w=f("Slideshow"),I=f("Next"),J=f("Previous"),W=f("Close"));s=f("Content").append(n=f("LoadedContent").css({width:0,height:0}),$controls,v=f("Title"));A.append(f().append(f("TopLeft"),R=f("TopCenter"),f("TopRight")),f().append(S=f("MiddleLeft"),s,T=f("MiddleRight")),f().append(f("BottomLeft"),U=f("BottomCenter"),f("BottomRight"))).children().children().css({"float":"left"});H=f(false,"position:absolute; width:9999px; visibility:hidden; display:none");c("body").prepend(u,
l.append(A,H));s.children().hover(function(){c(this).addClass("hover")},function(){c(this).removeClass("hover")}).addClass("hover");B=R.height()+U.height()+s.outerHeight(true)-s.height();C=S.width()+T.width()+s.outerWidth(true)-s.width();x=n.outerHeight(true);y=n.outerWidth(true);l.css({"padding-bottom":B,"padding-right":C}).hide();I.click(e.next);J.click(e.prev);W.click(e.close);s.children().removeClass("hover");c("."+K).live("click.colorbox",function(b){if(b.button!==0&&typeof b.button!=="undefined"||
b.ctrlKey||b.shiftKey||b.altKey)return true;else{e.launch(this);return false}});u.click(function(){a.overlayClose&&e.close()});c(document).bind("keydown",function(b){if(t&&a.escKey&&b.keyCode===27){b.preventDefault();e.close()}if(t&&a.arrowKey&&!F&&j[1])if(b.keyCode===37&&(h||a.loop)){b.preventDefault();J.click()}else if(b.keyCode===39&&(h<j.length-1||a.loop)){b.preventDefault();I.click()}})};e.remove=function(){l.add(u).remove();c("."+K).die("click").removeData(r).removeClass(K)};e.position=function(b,
d){function g(z){R[0].style.width=U[0].style.width=s[0].style.width=z.style.width;s[0].style.height=S[0].style.height=T[0].style.height=z.style.height}var k,p=Math.max(o.height()-a.h-x-B,0)/2+o.scrollTop(),D=Math.max(o.width()-a.w-y-C,0)/2+o.scrollLeft();k=l.width()===a.w+y&&l.height()===a.h+x?0:b;A[0].style.width=A[0].style.height="9999px";v.width(a.w);l.dequeue().animate({width:a.w+y,height:a.h+x+v.height(),top:p,left:D},{duration:k,complete:function(){g(this);F=false;A[0].style.width=a.w+y+C+"px";
A[0].style.height=a.h+x+B+v.height()+"px";d&&d()},step:function(){g(this)}})};e.resize=function(b){if(t){b=b||{};if(b.width)a.w=q(b.width,"x")-y-C;if(b.innerWidth)a.w=q(b.innerWidth,"x");n.css({width:a.w});if(b.height)a.h=q(b.height,"y")-x-B;if(b.innerHeight)a.h=q(b.innerHeight,"y");if(!b.innerHeight&&!b.height){b=n.wrapInner("<div style='overflow:auto'></div>").children();a.h=b.height();b.replaceWith(b.children())}n.css({height:a.h});e.position(a.transition==="none"?0:a.speed)}};e.prep=function(b){function d(p){var D,
z,Y,Z,G=j.length,$=a.loop;v.html(a.title||i.title||"");e.position(p,function(){function da(){E&&l[0].style.removeAttribute("filter")}if(t){E&&g&&n.fadeIn(100);if(a.iframe)c("<iframe frameborder=0"+(a.scrolling?"":" scrolling='no'")+(E?" allowtransparency='true'":"")+"/>").attr({src:a.href,name:(new Date).getTime()}).appendTo(n);$controls.show();n.show();v.show();if(G>1){V.html(a.current.replace(/\{current\}/,h+1).replace(/\{total\}/,G)).show();I[$||h<G-1?"show":"hide"]().html(a.next);J[$||h?"show":
"hide"]().html(a.previous);D=h?j[h-1]:j[G-1];Y=h<G-1?j[h+1]:j[0];if(a.slideshow){w.show();h===G-1&&!$&&l.is("."+m+"Slideshow_on")&&w.click()}if(a.preloading){Z=c(Y).data(r).href||Y.href;z=c(D).data(r).href||D.href;if(N(Z))c("<img/>")[0].src=Z;if(N(z))c("<img/>")[0].src=z}}loaded();a.transition==="fade"?l.fadeTo(k,1,function(){da()}):da();o.bind("resize."+m,function(){e.position(0)});c.event.trigger(O);a.onComplete&&a.onComplete.call(i)}})}if(t){var g,k=a.transition==="none"?0:a.speed;o.unbind("resize."+
m);n.remove();n=f("LoadedContent").html(b);n.hide().appendTo(H.show()).css({width:function(){a.w=a.w||n.width();if(a.minWidth){var p=q(a.minWidth,"x");if(a.w<p)a.w=p}if(a.mw&&a.mw<a.w)a.w=a.mw;return a.w}(),overflow:a.scrolling?"auto":"hidden"}).css({height:function(){a.h=a.h||n.height();if(a.minHeight){var p=q(a.minHeight,"y");if(a.h<p)a.h=p}if(a.mh&&a.mh<a.h)a.h=a.mh;return a.h}()}).prependTo(s);H.hide();c("#"+m+"Photo").css({cssFloat:"none"});Q&&c("select").not(l.find("select")).filter(function(){return this.style.visibility!==
"hidden"}).css({visibility:"hidden"}).one(P,function(){this.style.visibility="inherit"});a.transition==="fade"?l.fadeTo(k,0,function(){d(0)}):d(k)}};e.load=function(){var b,d,g,k=e.prep;F=true;i=j[h];a=c.extend({},c(i).data(r));aa();c.event.trigger(L);a.onLoad&&a.onLoad.call(i);a.h=a.height?q(a.height,"y")-x-B:a.innerHeight&&q(a.innerHeight,"y");a.w=a.width?q(a.width,"x")-y-C:a.innerWidth&&q(a.innerWidth,"x");a.mw=a.w;a.mh=a.h;if(a.maxWidth){a.mw=q(a.maxWidth,"x")-y-C;a.mw=a.w&&a.w<a.mw?a.w:a.mw}if(a.maxHeight){a.mh=
q(a.maxHeight,"y")-x-B;a.mh=a.h&&a.h<a.mh?a.h:a.mh}b=a.href;$controls.hide();v.hide();loading();if(a.inline){f("InlineTemp").hide().insertBefore(c(b)[0]).bind(L+" "+P,function(){c(this).replaceWith(n.children())});k(c(b))}else if(a.iframe)k(" ");else if(a.html)k(a.html);else if(N(b)){d=new Image;d.onload=function(){var p;d.onload=null;d.id=m+"Photo";c(d).css({margin:"auto",border:"none",display:"block",cssFloat:"left"});if(a.scalePhotos){g=function(){d.height-=d.height*p;d.width-=d.width*p};if(a.mw&&
d.width>a.mw){p=(d.width-a.mw)/d.width;g()}if(a.mh&&d.height>a.mh){p=(d.height-a.mh)/d.height;g()}}setTimeout(function(){k(d);if(a.h)d.style.marginTop=Math.max(a.h-d.height,0)/2+"px"},1);if(j[1]&&(h<j.length-1||a.loop))c(d).css({cursor:"pointer"}).click(e.next);if(E)d.style.msInterpolationMode="bicubic"};d.src=b}else f().appendTo(H).load(b,function(p,D,z){k(D==="error"?"Request unsuccessful: "+z.statusText:this)})};e.next=function(){if(!F){h=h<j.length-1?h+1:0;e.load()}};e.prev=function(){if(!F){h=
h?h-1:j.length-1;e.load()}};e.slideshow=function(){function b(){w.text(a.slideshowStop).bind(O,function(){g=setTimeout(e.next,a.slideshowSpeed)}).bind(L,function(){clearTimeout(g)}).one("click",function(){d()});l.removeClass(k+"off").addClass(k+"on")}var d,g,k=m+"Slideshow_";w.bind(ca,function(){w.unbind();clearTimeout(g);l.removeClass(k+"off "+k+"on")});d=function(){clearTimeout(g);w.text(a.slideshowStart).unbind(O+" "+L).one("click",function(){b();g=setTimeout(e.next,a.slideshowSpeed)});l.removeClass(k+
"on").addClass(k+"off")};if(a.slideshow&&j[1])a.slideshowAuto?b():d()};e.close=function(){if(t){t=false;c.event.trigger(P);a.onCleanup&&a.onCleanup.call(i);o.unbind("."+m+" ."+M);u.fadeTo("fast",0);l.stop().fadeTo("fast",0,function(){l.find("iframe").attr("src","about:blank");n.remove();l.add(u).css({opacity:1,cursor:"auto"}).hide();try{X.focus()}catch(b){}setTimeout(function(){c.event.trigger(ca);a.onClosed&&a.onClosed.call(i)},1)})}};e.element=function(){return c(i)};e.settings=ba;c(e.init)})(jQuery,
this);