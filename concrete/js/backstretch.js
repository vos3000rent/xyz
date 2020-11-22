!function(a,b,c){"use strict";a.fn.backstretch=function(d,f){return d!==c&&0!==d.length||a.error("No images were supplied for Backstretch"),0===a(b).scrollTop()&&b.scrollTo(0,0),this.each(function(){var b=a(this),c=b.data("backstretch");c&&(f=a.extend(c.options,f),c.destroy(!0)),c=new e(this,d,f),b.data("backstretch",c)})},a.backstretch=function(b,c){return a("body").backstretch(b,c).data("backstretch")},a.expr[":"].backstretch=function(b){return a(b).data("backstretch")!==c},a.fn.backstretch.defaults={centeredX:!0,centeredY:!0,duration:5e3,fade:0};var d={wrap:{left:0,top:0,overflow:"hidden",margin:0,padding:0,height:"100%",width:"100%",zIndex:-999999},img:{position:"absolute",display:"none",margin:0,padding:0,border:"none",width:"auto",height:"auto",maxHeight:"none",maxWidth:"none",zIndex:-999999}},e=function(c,e,g){if(this.options=a.extend({},a.fn.backstretch.defaults,g||{}),this.images=a.isArray(e)?e:[e],a.each(this.images,function(){a("<img />")[0].src=this}),this.isBody=c===document.body,this.$container=a(c),this.$wrap=a('<div class="backstretch"></div>').css(d.wrap).appendTo(this.$container),this.$root=this.isBody?a(f?b:document):this.$container,!this.isBody){var h=this.$container.css("position"),i=this.$container.css("zIndex");this.$container.css({position:"static"===h?"relative":h,zIndex:"auto"===i?0:i,background:"none"}),this.$wrap.css({zIndex:-999998})}this.$wrap.css({position:this.isBody&&f?"fixed":"absolute"}),this.index=0,this.show(this.index),a(b).on("resize.backstretch",a.proxy(this.resize,this)).on("orientationchange.backstretch",a.proxy(function(){this.isBody&&0===b.pageYOffset&&(b.scrollTo(0,1),this.resize())},this))};e.prototype={resize:function(){try{var a,c={left:0,top:0},d=this.isBody?this.$root.width():this.$root.innerWidth(),e=d,f=this.isBody?b.innerHeight?b.innerHeight:this.$root.height():this.$root.innerHeight(),g=e/this.$img.data("ratio");g>=f?(a=(g-f)/2,this.options.centeredY&&(c.top="-"+a+"px")):(g=f,e=g*this.$img.data("ratio"),a=(e-d)/2,this.options.centeredX&&(c.left="-"+a+"px")),this.$wrap.css({width:d,height:f}).find("img:not(.deleteable)").css({width:e,height:g}).css(c)}catch(a){}return this},show:function(b){if(!(Math.abs(b)>this.images.length-1)){this.index=b;var c=this,e=c.$wrap.find("img").addClass("deleteable"),f=a.Event("backstretch.show",{relatedTarget:c.$container[0]});return clearInterval(c.interval),c.$img=a("<img />").css(d.img).bind("load",function(b){var d=this.width||a(b.target).width(),g=this.height||a(b.target).height();a(this).data("ratio",d/g),a(this).fadeIn(c.options.speed||c.options.fade,function(){e.remove(),c.paused||c.cycle(),c.$container.trigger(f,c)}),c.resize()}).appendTo(c.$wrap),c.$img.attr("src",c.images[b]),c}},next:function(){return this.show(this.index<this.images.length-1?this.index+1:0)},prev:function(){return this.show(0===this.index?this.images.length-1:this.index-1)},pause:function(){return this.paused=!0,this},resume:function(){return this.paused=!1,this.next(),this},cycle:function(){return this.images.length>1&&(clearInterval(this.interval),this.interval=setInterval(a.proxy(function(){this.paused||this.next()},this),this.options.duration)),this},destroy:function(c){a(b).off("resize.backstretch orientationchange.backstretch"),clearInterval(this.interval),c||this.$wrap.remove(),this.$container.removeData("backstretch")}};var f=function(){var a=navigator.userAgent,c=navigator.platform,d=a.match(/AppleWebKit\/([0-9]+)/),e=!!d&&d[1],f=a.match(/Fennec\/([0-9]+)/),g=!!f&&f[1],h=a.match(/Opera Mobi\/([0-9]+)/),i=!!h&&h[1],j=a.match(/MSIE ([0-9]+)/),k=!!j&&j[1];return!((c.indexOf("iPhone")>-1||c.indexOf("iPad")>-1||c.indexOf("iPod")>-1)&&e&&e<534||b.operamini&&"[object OperaMini]"==={}.toString.call(b.operamini)||h&&i<7458||a.indexOf("Android")>-1&&e&&e<533||g&&g<6||"palmGetResource"in b&&e&&e<534||a.indexOf("MeeGo")>-1&&a.indexOf("NokiaBrowser/8.5.0")>-1||k&&k<=6)}()}(jQuery,window);