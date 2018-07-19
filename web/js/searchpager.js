function searchPage(elementId, page)
{
    document.getElementById(elementId).value = page;
    document.forms[0].submit();
}

function searchMrpp(elementId)
{
    var mrpp = document.getElementById("__search_mrpp_select").value;
    document.getElementById(elementId).value = mrpp;
    document.forms[0].submit();
}
