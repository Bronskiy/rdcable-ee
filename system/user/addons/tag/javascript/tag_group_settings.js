(function($, global){
	function shortName(a){var b="",c="_",f="",d={"223":"ss","224":"a","225":"a","226":"a","229":"a","227":"ae","230":"ae","228":"ae","231":"c","232":"e","233":"e","234":"e","235":"e","236":"i","237":"i","238":"i","239":"i","241":"n","242":"o","243":"o","244":"o","245":"o","246":"oe","249":"u","250":"u","251":"u","252":"ue","255":"y","257":"aa","269":"ch","275":"ee","291":"gj","299":"ii","311":"kj","316":"lj","326":"nj","353":"sh","363":"uu","382":"zh","256":"aa","268":"ch","274":"ee","290":"gj","298":"ii","310":"kj","315":"lj","325":"nj","352":"sh","362":"uu","381":"zh"};if(b!=="")if(a.substr(0,b.length)==b)a=a.substr(b.length);a=a.toLowerCase();b=0;for(var g=a.length;b<g;b++){var e=a.charCodeAt(b);if(e>=32&&e<128)f+=a.charAt(b);else if(d.hasOwnProperty(e))f+=d[e];}d=new RegExp(c+"{2,}","g");a=f;a=a.replace("/<(.*?)>/g","");a=a.replace(/\s+/g,c);a=a.replace(/\//g,c);a=a.replace(/[^a-z0-9\-\._]/g,"");a=a.replace(/\+/g,c);a=a.replace(d,c);a=a.replace(/-$/g,"");a=a.replace(/_$/g,"");a=a.replace(/^_/g,"");a=a.replace(/^-/g,"");return a.replace(/\.+$/g,"");}

	$(function(){
		var idWrapper = Solspace.idWrapper;
		var $context = $('#' + idWrapper);

		var $newGroupLink = $('.new_group_link', $context);
		var $cancelNewGroupLink = $('.cancel_new_group_link', $context);

		$newGroupLink.prop('disabled', false);

		$newGroupLink.click(function(e){
			$('.tag_group_select', $context).hide();
			$('.insert_new_tag_holder', $context).show();
			$('.new_tag_group_name', $context).prop('disabled', false);
			$('.new_tag_group_short_name', $context).prop('disabled', false);

			$newGroupLink.hide().prop('disabled', true);
			$cancelNewGroupLink.show().prop('disabled', false);

			e.preventDefault();
			return false;
		});

		$cancelNewGroupLink.click(function(e){
			$('.tag_group_select', $context).show();
			$('.insert_new_tag_holder', $context).hide();
			$('.new_group_link', $context).show();
			$('.new_tag_group_name', $context).val('').prop('disabled', true);
			$('.new_tag_group_short_name', $context).val('').prop('disabled', true);

			$newGroupLink.show().prop('disabled', false);
			$cancelNewGroupLink.hide().prop('disabled', true);

			e.preventDefault();
			return false;
		});

		var $new_tag_group_short_name 	= $('.new_tag_group_short_name', $context),
			$new_tag_group_name			= $('.new_tag_group_name', $context);

		$new_tag_group_name.keyup(function(e) {
			$new_tag_group_short_name.val(shortName($new_tag_group_name.val()));
		});
	});
}(jQuery, window));
