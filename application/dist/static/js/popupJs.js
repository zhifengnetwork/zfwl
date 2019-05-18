/**
 * 介绍:动态创建(错误)对话框。
 * 第一参数:icon路径。
 * 第二参数:title。
 * 第三个参数:提示文本。
 * **/
function sAFail(_iconUrl,_title,_text){
	var strF ='';
	strF += '<div class="maskWrap">';						
		strF += '<div class="rightAndWrongWrap">';
			strF +='<div class="rightAndWrongBox">';
				strF +='<p class="rAndWBoxTop">';
					strF +='<img class="rAndWBoxTopIcon" src="'+ _iconUrl +'" />';
				strF +='</p>';
				strF +='<p class="rAndWBoxTitle">'+ _title +'</p>';
				strF +='<p class="rAndWBoxText">'+ _text +'</p>';
			strF +='</div>';
			strF += '<p class="rightAndWrongWrap-buttonWrap" onclick="thisButFail(false)">确定</p>';
		strF += '</div>';
	strF += '</div>';
		
	/*返回值*/
	return strF;
}
/**
 * 介绍:动态创建(成功)对话框。
 * 第一参数:icon路径。
 * 第二参数:title。
 * 第三个参数:提示文本。
 * **/
function sASuccess(_iconUrl,_title,_text){
	var strS ='';
	strS += '<div class="maskWrap">';						
		strS += '<div class="rightAndWrongWrap">';
			strS +='<div class="rightAndWrongBox">';
				strS +='<p class="rAndWBoxTop">';
					strS +='<img class="rAndWBoxTopIcon" src="'+ _iconUrl +'" />';
				strS +='</p>';
				strS +='<p class="rAndWBoxTitle">'+ _title +'</p>';
				strS +='<p class="rAndWBoxText" id="rAndWBText">'+ _text +'</p>';
			strS +='</div>';
			strS += '<p class="rightAndWrongWrap-buttonWrap" onclick="thisButSuccess(true)">确定</p>';
		strS += '</div>';
	strS += '</div>';
		
	/*返回值*/
	return strS;
}

/**
 * 介绍:动态创建(警告)对话框。
 * 第一参数:icon路径。
 * 第二参数:title。
 * 第三个参数:提示文本。
 * **/
function warningAlert(_iconUrl,_title,_text){
	var strWarning = '';
	strWarning += '<div class="maskWrap">';			
		strWarning += '<div class="rightAndWrongWrap">';
			strWarning += '<div class="rightAndWrongBox">';
				strWarning += '<p class="rAndWBoxTop">';
					strWarning += '<img class="rAndWBoxTopIcon" src="'+ _iconUrl +'" />';
				strWarning += '</p>';
				strWarning += '<p class="rAndWBoxTitle">'+ _title +'</p>';
				strWarning += '<p class="rAndWBoxText">'+ _text +'</p>';
			strWarning += '</div>';
			strWarning += '<div class="rightAndWrongWrap-buttonWrap">';
				strWarning += '<p class="rightAndWrongWrap-buttonFalse" onclick="thisButton(false)">取消</p>';
				strWarning += '<p class="rightAndWrongWrap-buttonTrue" onclick="thisButton(true)">确定</p>';
			strWarning += '</div>';
		strWarning += '</div>';
	strWarning += '</div>';
	
	/*返回值*/
	return strWarning;
}

/**
 * (错误)弹框=>确认按钮
 * **/
var errorData = null;
function thisButFail(_judge){
	errorData = _judge;
	/*删除 => 弹框*/
	$('.maskWrap').remove();
	return false;
}
/**
 * (成功)弹框=>确认按钮
 * **/
var successData = null;
function thisButSuccess(_judge){
	successData = _judge;
	/*删除 => 弹框*/
	$('.maskWrap').remove();
	return false;
}
/**
 * (警告)弹框=>确认按钮
 * **/
var warningData = null;
function thisButton(_judge){
	warningData = _judge;
	/*删除 => 弹框*/
	$('.maskWrap').remove();
	return false;
}
