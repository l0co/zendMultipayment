<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>We are just about to take your money</title>
</head>
<body style="text-align:center">
  <div style="display: table; height: 100%; position: relative; overflow: hidden; width: 600px; text-align: left; margin: auto">
    <div style="position: absolute; top: 36%;display: table-cell; vertical-align: middle;">
      <div style="position: relative; top: -50%; width: 600px; text-align: center">
        <div style="margin-top: 10px; font-size: 22px">
          We are redirecting you now to the payment page.<br/>
        </div>
        <div style="margin-top: 10px; font-size: 26px; font-weight: bold">
          Please wait...
        </div>
      </div>
    </div>
  </div>
  
  <? /** The payment form is here **/ ?>
  <form action="<?=$action?>" method="<?=$method?>" id="payform">
    <? foreach($data as $name=>$value) : ?>
      <input type="hidden" name="<?=$name?>" value="<?=$value?>"/>
    <? endforeach; ?>
  </form>
  
  <script type="text/javascript">
    setTimeout(function() {
        document.getElementById("payform").submit();
    }, 1000);
  </script>
</body>
</html>
