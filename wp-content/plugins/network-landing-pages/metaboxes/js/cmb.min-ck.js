(function(e,t,n,r){e.CMB={formfield:"",iterator:0,file_frames:{},init:function(){CMB.log(e.cmb_l10);e.cmb_l10.new_admin_style&&n(".cmb-spinner img").hide();n(".cmb_timepicker").each(function(){n("#"+jQuery(this).attr("id")).timePicker({startTime:"07:00",endTime:"22:00",show24Hours:!1,separator:":",step:30})});n(".cmb_datepicker").each(function(){n("#"+jQuery(this).attr("id")).datepicker()});n("#ui-datepicker-div").wrap('<div class="cmb_element" />');typeof jQuery.wp=="object"&&typeof jQuery.wp.wpColorPicker=="function"?n("input:text.cmb_colorpicker").wpColorPicker():n("input:text.cmb_colorpicker").each(function(e){n(this).after('<div id="picker-'+e+'" style="z-index: 1000; background: #EEE; border: 1px solid #CCC; position: absolute; display: block;"></div>');n("#picker-"+e).hide().farbtastic(n(this))}).focus(function(){n(this).next().show()}).blur(function(){n(this).next().hide()});n(".cmb_metabox").on("change",".cmb_upload_file",function(){CMB.formfield=n(this).attr("name");n("#"+CMB.formfield+"_id").val("")}).on("click",".cmb_upload_button",function(t){t.preventDefault();var r=n(this);CMB.formfield=r.prev("input").attr("name");var i=n("#"+CMB.formfield),s=!0,o=!0,u=r.hasClass("cmb_upload_list");if(CMB.formfield in CMB.file_frames){CMB.file_frames[CMB.formfield].open();return}CMB.file_frames[CMB.formfield]=wp.media.frames.file_frame=wp.media({title:n("label[for="+CMB.formfield+"]").text(),button:{text:e.cmb_l10.upload_file},multiple:u?!0:!1});CMB.file_frames[CMB.formfield].on("select",function(){if(u){o=CMB.file_frames[CMB.formfield].state().get("selection").toJSON();i.val(o.url);n("#"+CMB.formfield+"_id").val(o.id);var e=[];n(o).each(function(){this.type&&this.type==="image"?s='<li class="img_status"><img width="50" height="50" src="'+this.url+'" class="attachment-50x50" alt="'+this.filename+'"><p><a href="#" class="cmb_remove_file_button" rel="'+CMB.formfield+"["+this.id+']">'+cmb_l10.remove_image+'</a></p><input type="hidden" id="filelist-'+this.id+'" name="'+CMB.formfield+"["+this.id+']" value="'+this.url+'"></li>':s="<li>"+cmb_l10.file+" <strong>"+this.filename+'</strong>&nbsp;&nbsp;&nbsp; (<a href="'+this.url+'" target="_blank" rel="external">'+cmb_l10.download+'</a> / <a href="#" class="cmb_remove_file_button" rel="'+CMB.formfield+"["+this.id+']">'+cmb_l10.remove_file+'</a>)<input type="hidden" id="filelist-'+this.id+'" name="'+CMB.formfield+"["+this.id+']" value="'+this.url+'"></li>';e.push(s)});n(e).each(function(){i.siblings(".cmb_media_status").slideDown().append(this)})}else{o=CMB.file_frames[CMB.formfield].state().get("selection").first().toJSON();i.val(o.url);n("#"+CMB.formfield+"_id").val(o.id);o.type&&o.type==="image"?s='<div class="img_status"><img style="max-width: 350px; width: 100%; height: auto;" src="'+o.url+'" alt="'+o.filename+'" title="'+o.filename+'" /><p><a href="#" class="cmb_remove_file_button" rel="'+CMB.formfield+'">'+cmb_l10.remove_image+"</a></p></div>":s=cmb_l10.file+" <strong>"+o.filename+'</strong>&nbsp;&nbsp;&nbsp; (<a href="'+o.url+'" target="_blank" rel="external">'+cmb_l10.download+'</a> / <a href="#" class="cmb_remove_file_button" rel="'+CMB.formfield+'">'+cmb_l10.remove_file+"</a>)";i.siblings(".cmb_media_status").slideDown().html(s)}});CMB.file_frames[CMB.formfield].open()}).on("click",".cmb_remove_file_button",function(e){var t=n(this);if(t.is(".attach_list .cmb_remove_file_button")){t.parents("li").remove();return!1}CMB.formfield=t.attr("rel");var r=t.parents(".img_status");n("input#"+CMB.formfield).val("");n("input#"+CMB.formfield+"_id").val("");r.length?r.html(""):t.parents(".cmb_media_status").html("");return!1}).on("click",".add-row-button",function(e){e.preventDefault();var t=n(this),r="#"+t.data("selector"),i=n(r),s=n(".empty-row",i).clone(!0);s.removeClass("empty-row").addClass("repeat-row");s.insertBefore(r+" tbody>tr:last");var o=n("input.cmb_datepicker",s),u=o.attr("id");o.attr("id",u+CMB.iterator);CMB.iterator++}).on("click",".remove-row-button",function(e){e.preventDefault();var t=n(this),r=t.parents(".cmb-repeat-table");CMB.log("number of tbodys",r.length);CMB.log("number of trs",n("tr",r).length);n("tr",r).length>2&&t.parents(".cmb-repeat-table tr").remove()}).on("keyup",".cmb_oembed",function(e){CMB.doAjax(n(this),e)});n(".cmb_oembed").bind("paste",function(e){var t=n(this);setTimeout(function(){CMB.doAjax(t,"paste")},100)}).blur(function(){setTimeout(function(){CMB.spinner(".postbox table.cmb_metabox",!0)},2e3)});setTimeout(CMB.resizeoEmbeds,500);n(e).on("resize",CMB.resizeoEmbeds)},log:function(){e.cmb_l10.script_debug&&console&&typeof console.log=="function"&&console.log.apply(console,arguments)},spinner:function(e,t){t?n(".cmb-spinner",e).hide():n(".cmb-spinner",e).show()},doAjax:function(t,r){var i=t.val();if(i.length<6)return;if(r==="paste"||r.which<=90&&r.which>=48||r.which>=96&&r.which<=111||r.which==8||r.which==9||r.which==187||r.which==190){var s=t.attr("id"),o=t.parents(".cmb_metabox tr td"),u=n(".embed_status",o),a=t.width(),f=n(":first-child",u);a=u.length&&f.length?f.width():t.width();CMB.spinner(o);n(".embed_wrap",o).html("");setTimeout(function(){if(n(".cmb_oembed:focus").val()!=i)return;n.ajax({type:"post",dataType:"json",url:e.cmb_l10.ajaxurl,data:{action:"cmb_oembed_handler",oembed_url:i,oembed_width:a>300?a:300,field_id:s,object_id:t.data("objectid"),object_type:t.data("objecttype"),cmb_ajax_nonce:e.cmb_l10.ajax_nonce},success:function(e){CMB.log(e);if(typeof e.id=="undefined")return;CMB.spinner(o,!0);n(".embed_wrap",o).html(e.result)}})},500)}},resizeoEmbeds:function(){n("table.cmb_metabox").each(function(e){var t=n(this),r=t.parents(".inside");if(!r.length)return!0;var i=r.width(),s=Math.round(i*.82*.97)-30;if(s>639)return!0;var o=n(".cmb-type-oembed .embed_status",t).children().first(),u=o.width(),a=o.height(),f=Math.round(s*a/u);o.width(s).height(f)})}};n(t).ready(CMB.init)})(window,document,jQuery);