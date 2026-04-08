function getCookie(name)
{
  cookie = " "+document.cookie;
  offset = cookie.indexOf(" "+name+"=");

  if (offset == -1) return undefined;

  offset += name.length+2;
  end     = cookie.indexOf(";", offset)

  if (end == -1) end = cookie.length;

  return unescape(cookie.substring(offset, end));
}

function establecerCookie(nombre, valor, caducidad = false, ruta = false, dominio = false) {
	var c = "";
	c += nombre + "=" + valor;
	//c += "; expires=" + caducidad;
	//c += "; path=" + ruta;
	//c += "; domain=" + dominio;
	document.cookie = c;
}

function imprimirEtiquetas(array_exp, usr, pass, etiquetadora, modelo_imp, host) {
	for (i = 0; i < array_exp.length; i++) {
		if (modelo_imp.indexOf('LASER') >= 0) {
			imprimir_etiqueta_laser(array_exp[i], usr, pass, etiquetadora, modelo_imp, host);
		} else {
			imprimir_etiqueta_normal(array_exp[i], usr, pass, etiquetadora, modelo_imp, host);
		}
	}
}

function imprimir_etiqueta_normal(codExpedicion, usr, pass, et, model, host) {
	
	var action = '/gestion_imprimir_ecommerce.do?user='+usr+'&valorImpresora='+model+'&codExpedicion='+codExpedicion;	
	// aquí no se utilizan cookies son datos de la configuración
	
	if (navigator.userAgent.match(/msie/i) || navigator.userAgent.match(/trident/i) ){ 
		// Applet
		document.getElementById('ImpEtiqueta').Imprimir(new String(action), new String(et));
	}
	
}

function imprimir_etiqueta_laser(codExpedicion, usr,pass,et, model,host){
	var url = host+'/gestion_imprimir_laser.do?user='+usr+'&pass='+pass+'&imp_magento='+et+'&codExpedicion='+codExpedicion+'&modelo='+model;
	var testframe = document.createElement('iframe');
    testframe.id = 'frameprint_codExpedicion';
    testframe.src = url;
    testframe.style.width=0;
    testframe.style.height=0;
    document.body.appendChild(testframe);
	//return window.open(url,'','height=550,width=820,modal=yes');
}
function procesando() {
	$("*").css("cursor", "wait");
	$("#content").fadeTo("fast",0.4);
}

function confirmar(str){		
	if (confirm(str)){
		procesando();
		return true ;
	}else{
		return false ;
	}
}
function IsANumber(InString, IntFlag) {
	 if(!parseFloat(InString)) {
			 return false ;
	 }
	 XX="" ;
	 for (var i=0; i<InString.length; i++) {
		X=InString.charAt(i) ;
		XX =XX + X ;
		if (IntFlag != "1") {
			if( (X != ".") && (X != ",") && (X != "-") && (X != "0") ) {
				if (!parseInt(X)) {
					return false ;
					break ;
				}
			}
		}
		if(IntFlag == "1") {
			if( (X != "-") && (X != "0") ) {
				if (!parseInt(X)) {
					return false ;
					break ;
				}
			}
		}
	 }
	 return true;
}
function potencia(Numero, Potencia) {
	   var i=0;
	   var resul=1;
 resul=Numero;
 for(i=1;i<Potencia;i++) resul=resul*Numero;
	   return resul;
	}	
	
	function isNumeric(sText) {
	  var validChars = "0123456789,";
	  var isNumber = true;
	  var char;
	  for (i=0; i <sText.length && isNumber == true; i++) { 
	    char = sText.charAt(i); 
	    if (validChars.indexOf(char) == -1) isNumber = false;
	  }
	  return isNumber;
}
function ValidarNum(Numero, Campo, NumEnteros, NumReales) {

  if (Campo.readOnly==true) return false;
  Numero = Numero.replace(',','.');
  var SiNo = IsANumber (Numero,2);
  if (SiNo == false) Campo.value=0;
  var ParteEntera = 0;
  var ParteReal = 0;
  var Valor = new String(Campo.value);	 
  var Pos = Valor.indexOf(",");
  if (Pos<0)
    Pos = Valor.indexOf(".");
  if (Pos!=-1) {
  	ParteEntera = Valor.substring(0,Pos).replace(".","");
			if (NumReales>0)
					ParteReal = Valor.substring(Pos+1,Pos+NumReales+1);
	  } else ParteEntera = Valor;
   if (ParteEntera < potencia(10,NumEnteros)) {
		  	if (NumReales>0)
				  	Campo.value = ParteEntera + "," + ParteReal;
					else
				Campo.value = ParteEntera;
   } else {
	   Campo.value = 0;
	   Campo.focus();
   }
}

function soloNumeros(e) {
	key = (document.all) ? event.keyCode : e.which;
	return ((key == 46) || (key <= 13) || (key == 44) || ((key >= 48) && (key <= 57)));
}

// Aquí ejecuta el formulario de Unitaria
function blurDateValidation(customerDate) {
	var valida = validateFutureDate(customerDate);
	if (valida) saveDateLocalStorage(customerDate);
	return valida;
}

function formattedNow() {
	var today = new Date();
	var yyyy = today.getFullYear();
	let mm = today.getMonth() + 1; // Months start at 0!
	let dd = today.getDate();

	if (dd < 10) dd = '0' + dd;
	if (mm < 10) mm = '0' + mm;

	return now = yyyy + '-' + mm + '-' + dd;
}

// Validar que la fecha no es una anterior
function validateFutureDate(customerDate) {
	//var now = moment(new Date()).format("YYYY-MM-DD");
	var now = formattedNow();
	return customerDate >= now;
}

function saveDateLocalStorage(customerDate) {
	localStorage.nacex_fec = customerDate;
}