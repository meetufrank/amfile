(function () {
    var change = 'orientationchange' in window ?'orientationchange' :'resize';
    function calculate() {
        var deviceWidth = document.documentElement.clientWidth;
        if(deviceWidth<320){
            deviceWidth = 320;
        }else if(deviceWidth>750){
            deviceWidth = 750;
        }
        document.documentElement.style.fontSize = deviceWidth/7.5 + 'px';
    }
    //window.addEventListener(change,calculate,false);
    if(window.addEventListener){
        this.addEventListener(change,calculate,false);
    }else if(window.attachEvent){
        this.attachEvent('on' + change,calculate,false);
    }else{
        this['on' + change] = fn;
    }
    calculate();
})();