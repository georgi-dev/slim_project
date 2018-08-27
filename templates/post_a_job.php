<html>
<head>
	<title>Post a job</title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css" integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">
<script src="https://code.jquery.com/jquery-3.1.1.slim.min.js" integrity="sha384-A7FZj7v+d/sdmMqp/nOQwliLvUsJfDHW+k9Omg/a/EheAdgtzNs3hpfag6Ed950n" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js" integrity="sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/js/bootstrap.min.js" integrity="sha384-vBWWzlZJ8ea9aCX4pEW3rVHjgjt7zpkNpZk+02D9phzyeVkE+jo0ieGizqPLForn" crossorigin="anonymous"></script>
</head>
<body>	
	<h1 class="text-center">Post a job</h1>


	<form method="post" action="../save_a_posted_job" style="width:600px;margin:100px auto;">
	  <div class="form-group">
	    <label for="exampleInputEmail1">Аз търся</label>
	    <select name="job_kind">
	    	<option>Зидар</option>
	    	<option>Бояджия</option>
	    	<option>Шпакловчик</option>
	    </select>
	  </div>
	  <div class="form-group">
	    <label for="exampleInputPassword1">Дайте заглавие на вашата обява за работа</label>
	    <input type="text" class="form-control" name="job_title" id="exampleInputPassword1" placeholder="Добавете заглавие">
	  </div>
	  <div class="form-group">
	    <label for="exampleInputEmail1">Описание на работата</label>
	    <textarea class="form-control" name="job_description" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Добавете описание"></textarea>
	    
	  </div>

	  <div class="form-group">
	    <label for="exampleInputEmail1">Завършете вашата обява</label>
	    
	    <input type="email" class="form-control" name="client_email" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Добавете имейл">
	    
	  </div>
	  <button type="submit" class="btn btn-primary submit">Публикувай обявата</button>
	</form>

</body>
</html>

<script type="text/javascript">
	$('.submit').on('click', function(){
		alert('submited');
	});
</script>