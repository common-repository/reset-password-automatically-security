jQuery(document).ready(function(){
    CharSymbolShow();
 jQuery("input:radio[name=character_type]").click(function() {
    CharSymbolShow();
  });
 
});  

function CharSymbolShow(){
    var character_value = jQuery("input:radio[name=character_type]:checked").val();
    if(character_value == "pronounceable"){
      jQuery("#character_symbol").hide();
      jQuery("#min_digit").hide();
    }else{
      jQuery("#character_symbol").show();
      jQuery("#min_digit").show();
    }
  }