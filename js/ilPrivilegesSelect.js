/**
 * Selects all checkboxes that belong to a certain privilege type.
 * 
 * @author Alexander Keller <a.k3ll3r@gmail.com>
 * 
 * @param {Object} a_form the form
 * @param {Object} a_varname the id of the class
 * @param {Object} a_type the privilege type
 * @param {Object} a_elements the privileges that belong to a privilege type
 */
function setCheckboxes(a_form, a_varname, a_type, a_elements)
{
	// the handling of the "select all" checkbox itself
    if (document.forms[a_form].elements['select_' + a_varname + '_' + a_type].checked == false) 
    {
		check = false;
    }
	else
    {
		check = true;
    }

    // the handling of the checkboxes that belong to the "select all" checkbox
	for (i = 0; i < a_elements.length; i++) 
	{
		if (typeof(document.forms[a_form].elements['priv_' + a_varname + '_' + a_elements[i]]) != 'undefined') 
		{
    		document.forms[a_form].elements['priv_' + a_varname + "_" + a_elements[i]].checked = check;
    	}
    }

    return true;
}
