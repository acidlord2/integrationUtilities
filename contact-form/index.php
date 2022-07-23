<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,700,900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="fonts/icomoon/style.css">

    <link rel="stylesheet" href="css/owl.carousel.min.css">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    
    <!-- Style -->
    <link rel="stylesheet" href="css/style.css">

    <title>Форма для сообщения</title>
  </head>
  <body>
  

  <div class="content">
    
    <div class="container">

      
      <div class="row justify-content-center">
        <div class="col-md-10">
          
          <div class="row align-items-center">
            <div class="col-lg-7 mb-5 mb-lg-0">

              <h2 class="mb-5">Заполните форму ниже</h2>

              <form class="border-right pr-5 mb-5" method="post" id="contactForm" name="contactForm">
                <div class="row">
                  <div class="col-md-12 form-group">
                    <input type="date" class="form-control" name="date" id="date" value="<?php echo date('Y-m-d', strtotime('now'));?>">
                  </div>
                  <div class="col-md-12 form-group">
                    <input type="text" class="form-control" name="nps" id="nps" placeholder="НПС">
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-12 form-group">
                    <textarea class="form-control" name="message" id="message" cols="30" rows="7" placeholder="Сообщение"></textarea>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-12">
                    <input type="submit" value="Отправить данные" class="btn btn-primary rounded-0 py-2 px-4">
                    <span class="submitting"></span>
                  </div>
                </div>
              </form>

              <div id="form-message-warning mt-4"></div> 
              <div id="form-message-success">
                Ваше сообщение успешно отправлено
              </div>

            </div>
            <div class="col-lg-4 ml-auto">
              <h3 class="mb-4">Тестовая форма</h3>
              <p>Форма была создана в тестовых целях продемонстрировать возможности отправки данных в платформу Comindware Business Application Platform с любого устройства нашей планеты</p>
            </div>
          </div>
        </div>  
        </div>
      </div>
  </div>
    
    

    <script src="js/jquery-3.3.1.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.validate.min.js"></script>
    <script src="js/main.js?v=3"></script>
  </body>
</html>