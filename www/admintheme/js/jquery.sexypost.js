/*!
 * jQuery.sexyPost v1.0.3
 * http://github.com/jurisgalang/jquery-sexypost
 *
 * Copyright 2010 - 2011, Juris Galang
 * Dual licensed under the MIT or GPL Version 2 licenses.
 *
 * Date: 2011-01-07 11:00:12 -0800
 */

(function($){$.fn.sexyPost=function(options){var events=["start","progress","complete","error","abort","filestart","filecomplete"];var config={async:true,autoclear:false,start:function(event){},progress:function(event,completed,loaded,total){},complete:function(event,responseText){},error:function(event){},abort:function(event){}};if(options)$.extend(config,options);this.each(function(){for(event in events){if(config[events[event]]){$(this).bind("sexyPost."+events[event],config[events[event]]);}}
var form=$(this);form.submit(function(){var action=$(this).attr("action");var method=$(this).attr("method");send(this,action,method,config.async);return false;});$(".submit-trigger",form).not(":button").bind("change",function(){form.trigger("submit");});$(".submit-trigger",form).not(":input").bind("click",function(){form.trigger("submit");});var xhr=new XMLHttpRequest();xhr.onloadstart=function(event){form.trigger("sexyPost.start");}
xhr.onload=function(event){if(config.autoclear&&(xhr.status>=200)&&(xhr.status<=204))clearFields(form);form.trigger("sexyPost.complete",[xhr.responseText]);}
xhr.onerror=function(event){form.trigger("sexyPost.error");}
xhr.onabort=function(event){form.trigger("sexyPost.abort");}
xhr.upload["onprogress"]=function(event){var completed=event.loaded/event.total;form.trigger("sexyPost.progress",[completed,event.loaded,event.total]);}
function clearFields(form){$(":input",form).not(":button, :submit, :reset, :hidden").removeAttr("checked").removeAttr("selected").val("");}
function send(form,action,method,async){var data=new FormData();$.each($(form).serializeArray(),function(index,field){data.append(field.name,field.value);});$("input:file",form).each(function(i,field){var files=field.files;for(i=0;i<files.length;i++)data.append(field.name,files[i]);});xhr.open(method,action,async);xhr.setRequestHeader("Cache-Control","no-cache");xhr.setRequestHeader("X-Requested-With","XMLHttpRequest");xhr.send(data);}});return this;}})(jQuery);