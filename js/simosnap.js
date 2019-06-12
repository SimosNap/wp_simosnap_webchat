
function escapeTags(str) {
	return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function popupform(url,windowname,target){
    if (target == 'blank') {
        return true;
    } else {
    	if (target == 'full') {
            var w= screen.availWidth;
            var h= screen.availHeight;		
    	} else {
            if((screen.height/screen.width)<='1,34'){
                var w=((screen.availWidth*90)/100);
                var h=((screen.availHeight*90)/100);
            } else {
                var w=((screen.availWidth*95)/100);
                var h=((screen.availHeight*95)/100);
            }
    	}
        if(!window.focus)return true;
        window.open(url,windowname,'height='+h+' ,width='+w+', top=0, left=0, scrollbars=no');

    return true;
    }
}


function trim_whitespaces (str) {
    	return str.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
}
var needpass = false;
jQuery( document ).ready(function() {

    jQuery("#tabs").tabpager({
        //  maximum visible items
        items: 2,
        // CSS class for tabbed content
        contents: 'contents',
        // transition speed
        time: 300,
        // text for previous button
        previous: '',
        // text for next button
        next: '',
        // initial tab
        start: 1,
        // top or bottom
        position: 'bottom',
        // scrollable
        scroll: false
    });

    jQuery('#nickinput').on('blur keyup change click', function() {
      var checkname=jQuery(this).val();
      var availname=trim_whitespaces(checkname);
      if(availname!=''){
         var String = availname;

         jQuery.ajax({
                type: "GET",
                url: "https://www.simosnap.org/rest/service.php/checknick/"+String,
                success: function(result){
                    if(result['registered'] == "no") {    
                        jQuery('#nspwd').css({ "display": "none"});
                         jQuery('#nspwdlabel').css({ "display": "none"});
                        jQuery('#nsnotify').css({ "display": "none"});
                        jQuery('#nickinput').css({ "color": "green"});
                        jQuery('.have_pass td').html('');
                         needpass = false;
                    } else {
                        jQuery('#nspwd').css({ "display": "table-row"});
                        jQuery('#nspwdlabel').css({ "display": "block"});
                        jQuery('#nsnotify').css({ "display": "block"});
                        jQuery('#nickinput').css({ "color": "darkred"});
                        //$('.have_pass td').html('<p class="pass-alert"><i class="icon-lock"></i> Il Nickname scelto risulta registrato.</p>');
                         needpass = true;                                                       
                    }
                }
            });
      }
   });
});

function validateForm() {
    var x=document.forms["kiwiircform"]["nick"].value;
	var s=document.forms["kiwiircform"]["target"].value;
    if (x==null || x=="")  {
        jQuery('.nickerror').fadeIn("slow", function() {
            jQuery('.nickerror').fadeOut(2400);
            jQuery('#nickinput').focus();
        });
    return false;
    } else {
        popupform(this, 'chat', s);
    }
}