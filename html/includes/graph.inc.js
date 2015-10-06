// Load visualization API
google.load('visualization', '1.0', {'packages':['corechart']});

function drawChart(url){
	$.ajax({
		url: url,
		dataType: "json",
		success: function(jsonData){
			var data = new google.visualization.DataTable(jsonData);
	
			var chart = new google.visualization.PieChart(document.getElementById('chart_div'));
			var options = {height: 600,pieHole:0.5,tooltip:{trigger:'selection'}};
			chart.draw(data,options);
			google.visualization.events.addListener(chart,'onmouseover',function(entry){
				chart.setSelection([{row:entry.row}]);
			});
			google.visualization.events.addListener(chart, 'onmouseout', function(entry) {
				chart.setSelection([]);
			});
			$(window).resize(function(){
		        chart.draw(data,options);
		    });
		},
		error: function(jsonData){
			console.log(jsonData.responseText);
		}
	});
}