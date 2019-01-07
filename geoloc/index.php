<!DOCTYPE html>
<html lang="fr" xmlns="http://www.w3.org/1999/xhtml">
   <head>
      <meta charset="utf-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge" />
      <meta name="viewport" content="width=device-width, initial-scale=1" />
      <title>Test Geoloc</title>

      <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" />
      <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css" />
      <link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css" />
   </head>
   
   <body>
      <h1>Geolocation Web Service test page</h1>

      <hr>

      <div class="container">
          <form id="rest" method="POST" action="../../plugins/webservices/xmlrpc.php" enctype="multipart/form-data">
<!--         <form id="rest" method="POST" action="https://kiosks.ipmfrance.com/plugins/webservices/xmlrpc.php" enctype="multipart/form-data">-->
            <div class="row">
               <div class="col-xs-10 col-offset-1">
                  <div class="row">
                     <div class="col-xs-3">
                        <label for="method">Mots</label>
                        <select id="action" class="form-control">
                        <option value="recupererListe">recupererListe</option>
                        <option value="rechercherListe">rechercherListe</option>
                        <option value="rechercherListeDev" selected>rechercherListeDev</option>
                        <option value="detailsGaam">detailsGaam</option>
                        </select>
                        <input id="method" type="hidden" name="method" value="kiosks.getGeoloc"/>
                     </div>
                     <div class="col-xs-3">
                        <label for="nombre">Nombre</label>
                        <input id="nombre" type="text" name="nombre" value="10"/>
                     </div>
                     <div class="col-xs-3">
                        <label for="rayon">Rayon</label>
                        <input id="rayon" type="text" name="rayon" value="10"/>km
                     </div>
                     <div class="col-xs-3">
                        <label for="borneinf">Delta</label>
                        <input id="borneinf" type="text" name="borneinf" value="5"/>km
                        <input id="bornesup" type="hidden" name="bornesup" value="20"/>
                     </div>
                  </div>

                  <div class="row">
                     <div class="col-xs-3">
                        <label for="latu">Latitude utilisateur</label>
                        <input id="latu" type="text" name="latu" value="48.789776"/>
                     </div>
                     <div class="col-xs-3">
                        <label for="longu">Longitude utilisateur</label>
                        <input id="longu" type="text" name="longu" value="2.2871810000000323"/>
                     </div>
                     <div class="col-xs-3">
                        <label for="latr">Latitude recherche</label>
                        <input id="latr" type="text" name="latr" value="45.044236"/>
                     </div>
                     <div class="col-xs-3">
                        <label for="longr">Longitude recherche</label>
                        <input id="longr" type="text" name="longr" value="5.052735"/>
                     </div>
                  </div>

                  <div class="row">
                     <div class="col-xs-3">
                        <label for="idGaam">Id Gaam</label>
                        <input id="idGaam" type="text" name="idGaam" value="ek3k-cnam-0015"/>
                     </div>
                  </div>
               </div>
            </div>
         </form>
      </div>

      <div class="row" style="margin-top:25px;">
         <div class="col-xs-2 col-xs-offset-1">
            <button id="calcul" type="button" class="btn btn-lg btn-primary" >Get Geoloc</button>
         </div>
         <div class="col-xs-1">
            <img id="wait" src="wait.gif" style="display:none;" alt=""/>
         </div>
      </div>

      <div class="row" style="margin:25px;">
         <div id="xml" class="col-xs-6" style="display:none">
         </div>
      </div>

      <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
      <script type="text/javascript" src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
      <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js"></script>
      <script type="text/javascript">

      function xml_to_string(xml_node) {
         if (xml_node.xml)
            return xml_node.xml;
         else if (XMLSerializer) {
            var xml_serializer = new XMLSerializer();
            return xml_serializer.serializeToString(xml_node);
         } else {
            alert("ERROR: Extremely old browser");
            return "";
         }
      }

      $(function() {
         $('#calcul').click(function() {
            var request_recupererListe = "<"+"?xml version=&quot;1.0&quot; encoding=&quot;UTF-8&quot; standalone=&quot;no&quot; ?><root><recupererListe></recupererListe></root>";
            var request_rechercherListe = "<"+"?xml version=&quot;1.0&quot; encoding=&quot;UTF-8&quot; standalone=&quot;no&quot; ?><root><rechercherListe><latitudeUtilisateur>"+$("#latu").val()+"</latitudeUtilisateur><longitudeUtilisateur>"+$("#longu").val()+"</longitudeUtilisateur><latitudeRecherche>"+$("#latr").val()+"</latitudeRecherche><longitudeRecherche>"+$("#longr").val()+"</longitudeRecherche><rayon>"+$("#rayon").val()+"</rayon><nbrMax>"+$("#nombre").val()+"</nbrMax><borneInf>"+$("#borneinf").val()+"</borneInf><borneSup>"+$("#bornesup").val()+"</borneSup></rechercherListe></root>";
            var request_rechercherListeDev = "<"+"?xml version=&quot;1.0&quot; encoding=&quot;UTF-8&quot; standalone=&quot;no&quot; ?><root><rechercherListeDev><latitudeUtilisateur>"+$("#latu").val()+"</latitudeUtilisateur><longitudeUtilisateur>"+$("#longu").val()+"</longitudeUtilisateur><latitudeRecherche>"+$("#latr").val()+"</latitudeRecherche><longitudeRecherche>"+$("#longr").val()+"</longitudeRecherche><rayon>"+$("#rayon").val()+"</rayon><nbrMax>"+$("#nombre").val()+"</nbrMax><borneInf>"+$("#borneinf").val()+"</borneInf><borneSup>"+$("#bornesup").val()+"</borneSup></rechercherListeDev></root>";
            var request_detailsGaam = "<"+"?xml version=&quot;1.0&quot; encoding=&quot;UTF-8&quot; standalone=&quot;no&quot; ?><root><detailsGaam><identifiants>"+$('#idGaam').val()+"</identifiants></detailsGaam></root>";
            var request = eval("request_"+$('#action').val());

            $('#xml').text("...");
            $('#xml').show();
            
            $.ajax({
               type:"POST",
               url: '../../plugins/kiosks/geoloc/rest.php',
               data: {
                  xml: request,
                  method: $('#method').val()
               }
            }).done(function(data, xml) {
                console.info("Success, got data: ", data);
//                console.log("Success, got data: ", xml_to_string(data));
               $('#xml').text("Success: " + xml_to_string(data));
               $('#xml').show();
            }).fail(function(data, textStatus) {
                console.error("Failure, got data: ", data);
//                console.log("Failure, got data: ", xml_to_string(data));
               $('#xml').text("Failure: see the browser console error log!");
               $('#xml').show();
            });
         });
      });
      </script>
   </body>
</html>
