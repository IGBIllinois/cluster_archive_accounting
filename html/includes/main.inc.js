function confirm_disable_user() {
	return confirm("Are you sure you wish to delete this user?");
}

function cfop_advance(num){
	var inputname = "cfop_"+num;
	var nextinput = "cfop_"+(num+1);
	var length = document.forms["form"][inputname].value.length;
	if(length == document.forms["form"][inputname].maxLength){
		document.forms["form"][nextinput].focus();
	}
}