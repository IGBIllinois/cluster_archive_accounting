function confirm_disable_user() {
	return confirm("Are you sure you wish to delete this user?");
}

function confirm_disable_directory(){
	return confirm("Are you sure you wish to delete this directory?");
}

function cfop_advance(num){
	var inputname = "cfop_"+num;
	var nextinput = "cfop_"+(num+1);
	var length = document.forms["form"][inputname].value.length;
	if(length == document.forms["form"][inputname].maxLength){
		document.forms["form"][nextinput].focus();
	}
}

function directory_toggle(e){
	var hasdir = this.checked;
	var dnb = document.forms["form"]["do_not_bill"].checked
	document.forms["form"]["cfop_1"].disabled = !hasdir || dnb;
	document.forms["form"]["cfop_2"].disabled = !hasdir || dnb;
	document.forms["form"]["cfop_3"].disabled = !hasdir || dnb;
	document.forms["form"]["cfop_4"].disabled = !hasdir || dnb;
	document.forms["form"]["activity_code"].disabled = !hasdir || dnb;
	document.forms["form"]["do_not_bill"].disabled = !hasdir;
	document.forms["form"]["archive_dir"].disabled = !hasdir;
}

function bill_toggle(e){
	var dnb = this.checked;
	document.forms["form"]["cfop_1"].disabled = dnb;
	document.forms["form"]["cfop_2"].disabled = dnb;
	document.forms["form"]["cfop_3"].disabled = dnb;
	document.forms["form"]["cfop_4"].disabled = dnb;
	document.forms["form"]["activity_code"].disabled = dnb;
}