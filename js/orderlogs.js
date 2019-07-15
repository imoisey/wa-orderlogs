$(function () {
	// Получаем ID логов
	/*$.post('?plugin=orderlogs&module=logs', {order_id:$.order.id}, function (response){
		if(response.status == 'ok') {
			alert(response.data);
		}
	}, 'json'); */

	//console.log($.order.id);

	if( $('p').is('.workflow-actions') ) {
		//alert($.order.id);
		console.log('YES');
	}
});