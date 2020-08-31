$(document).ready( function(){
	// Initiate row sums and percentage helpers
	$('.count-grouped-data').each( function(){
		// Aggregate row sum and percent count
		var count = 0.0;
		$(this).find('td').each( function(){
			count += parseFloat( $(this).attr('data-count') );
		});

		var per_count = (100/count).toFixed(2);

		$(this).attr('data-total', count);
		$(this).attr('data-per-count', per_count);

		// Update each cell with its percentage 
		$(this).find('td').each( function(){
			var cellPercentage = parseFloat( ( parseInt($(this).attr('data-count'))*per_count ) ).toFixed(1);
			$(this).attr('data-percent', cellPercentage);
		});
	});
});

$(document).on('click', '.toggleNumbers', function(e) {
	e.preventDefault();
	var show = 'count';
	if( $(this).html() == 'Vis prosent' ) {
		show = 'percent';
		$(this).html('Vis tall');
	} else {
		$(this).html('Vis prosent');
	}

	$('#' + $(this).attr('data-target') + ' tr.count-data td').each( function(){
		$(this).html( $(this).attr('data-'+ show) + (show=='percent'?'%':'') );
	});
});

function drawCharts() {
	
	$('.graph').each(function(){
		var data_table_id = $(this).attr('data-table');
		var graph_header = $('#'+ data_table_id).attr('data-header');
		var type = $('#'+ data_table_id).attr('data-type');

		var data = [];
		
		if( type == 'group' ) {
			var headers = ['hm'];
			$('#'+ data_table_id + ' tbody th').each( function(){
				headers.push( $(this).html() );
			});
//			headers.push({role:'annotation'});
			data.push( headers );
			
			// number of columns
			var num_columns = $('#'+ data_table_id + ' thead th').length - 1;
			
			console.log( headers );
			console.log('Total num of columns: '+ num_columns );
			
			// find the column headers
			// setup counter objects
			var count = 0;
			var answers = [];
			$('#'+ data_table_id + ' thead th').each( function(){
				count++;
				if( count == 1 ) {
					return;
				}
				
				var answerCounter = [ $(this).html() ]
				answers.push( answerCounter );
			});
			
			// loop all answer rows
			$('#'+ data_table_id + ' tbody tr ').each( function(){
				var loop = 0;
				$(this).find('td').each( function(){
					answers[ loop ].push( parseInt( $(this).html() ) );
					loop++;
				});
			});
			
			for( i=0; i<answers.length; i++ ) {
				data.push( answers[i] );
			}

			console.info(answers);
			console.info(data);
			
			var graph_options = {
				isStacked: true,
				legend: { position: 'top', maxLines: 3 },
				bar: { groupWidth: '75%' },
			};
			
		} else {
			data.push(['Label', 'Value']);
			// Add headers
			$('#'+ data_table_id + ' tbody tr').each( function(){
				data.push([
					$(this).find('th:first').html(),
					parseInt( $(this).find('td:first').html())
				]);
			});
			var graph_options = {
			};
		}
		
		var graph_data = google.visualization.arrayToDataTable( data );
		
		if( type == 'group' ) {
			var chart = new google.visualization.ColumnChart( document.getElementById( 'graph-for-'+ data_table_id ) );	
		} else {
			var chart = new google.visualization.PieChart( document.getElementById( 'graph-for-'+ data_table_id ) );
		}
		chart.draw( graph_data, graph_options);

	});
}

$(document).ready( function(){
	google.charts.setOnLoadCallback(drawCharts);
});