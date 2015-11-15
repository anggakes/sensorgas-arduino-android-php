<?php $id_sensor = (isset($_GET['id_sensor'])) ? $_GET['id_sensor'] : "12345"; ?>
<center>
<h3>Status : <span id='status'> </span></h3>
<div style='width:300px'>
<iframe width="100%" height="250" marginwidth="0" marginheight="0" scrolling="auto" frameborder="0" src="https://thingspeak.com/channels/59582/charts/1?dynamic=true&amp;width=300&amp;results=15&amp;title=Sensor Gas"></iframe></iframe>
</center>
</div>

<script type="text/javascript" src="assets/jquery/jquery-2.1.4.min.js"></script>
<script type="text/javascript" language="javascript"> 

$.ajax({
	url : "status/<?= $id_sensor ?>",
	dataType:"json"
}).done(function(data){
	console.log(data);
	$("#status").html(data.statusgas);
});
</script>
