window.onbeforeunload = function(){
    var date = new Date();
    date.setDate(date.getDate() - 1);
    document.cookie = "evolved_key=; expires=" + date.toUTCString() + "; domain=" + window.location.hostname + "; path=/";
}