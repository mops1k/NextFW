<html>
<head>
    <title>Index template</title>
    {jQuery}
    {bootstrap}
    <script type="text/javascript">
    $(document).ready(function($) {
        $("#reload").click(function(event) {
            /* Act on the event */
            $("#captcha").attr("src","/captcha");
        });

        var location = window.location.pathname;
        $("ul.nav li").each(function() {
            if($(this).children('a').attr('href') == location)
                $(this).attr('class', 'active');
        });
    });
    </script>
    <style>
        body { padding-top: 70px; }
    </style>
</head>
<body>

<!-- navbar -->
<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
  <div class="container">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="/">phpdev.su</a>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
        <li><a href="/">Main</a></li>
        <li><a href="/parser">freelance.ru parser</a></li>
        <li><a href="/weather">weather in moscow</a></li>
      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>
<!-- end navbar -->
<div class="container">

<div class="row">
    <ol class="breadcrumb">
      <li><a href="/">Home</a></li>
      <li class="active">[breadcrumb]<a href="/">Captcha preview</a>[/breadcrumb]</li>
    </ol>
</div>

[content]
<div class="jumbotron">
    <p>Welcome to Next FRAMEWORK.<br />You are on {method}<br /><br />
    <b>Captcha image:</b><br />
    <img src="/captcha" alt="captcha" id="captcha" class="img-thumbnail"><br />
    <button class="btn btn-warning" id="reload">Reload IMAGE</button><br /><br />
    Exec time: {time}</p>
</div>
[/content]

</div>

</body>
</html>