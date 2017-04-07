function show(){
	var ul_tools = document.getElementById("ul-tools");
	var btn = document.getElementById("btn-tools");
	var value = ul_tools.style.display;
	
	if (value == "none") {
		ul_tools.style.display = "block";
		btn.innerHTML = ">>";
	} else {
		ul_tools.style.display = "none";
		btn.innerHTML = "<<";
	}
}

