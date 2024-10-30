function ihfcSubmitAction(obj, action)
{    
    var $form=jQuery(obj.form);
    jQuery("#ihfc-action").val(action);
    jQuery(obj).attr("disabled",true);
    $form.submit();
    return false;
}