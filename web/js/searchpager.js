function searchMrpp(elementId)
{
    var mrpp = document.getElementById("__search_mrpp_select").value;
    document.getElementById(elementId).value = mrpp;
    document.forms[0].submit();
}
