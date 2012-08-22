        function checkImages() {
 
       var divs=document.getElementsByTagName("DIV");
 
      for (var i=0;i<divs.length;i++) {
        if (divs[i].className == "imgcaption" || divs[i].className == "imgcaptionleft" || divs[i].className == "imgcaptionright") {
 
            var children = divs[i].getElementsByTagName("IMG");
            // check if there is a link encapsulating the image        
            var tmpImg = divs[i].childNodes[0].childNodes[0];
            if (tmpImg === null)
                tmpImg = divs[i].childNodes[0];
            else {
                // we have link and we can build the link image
                var innerElements = divs[i];
                var iLink = innerElements.childNodes[1].childNodes[2];
                var iSpan = iLink.childNodes[0];
                // set the href of the link to the image link
                iLink.href= innerElements.childNodes[0].href;
                // show the link image
                iSpan.style.display="inline";
            }
            //var tmpLink = divs[i].childNodes[0];
            divs[i].style.width=(tmpImg.width + 8)+"px";     
        }
      }
  }
 
 
  if(window.toolbar!=undefined){
    toolbar[toolbar.length] = {"type":"format",
                             "title":"Adds an ImageCaption tag",
                             "icon":"../../plugins/imagereference/button.png",
                             "key":"",
                             "open":"<imgcaption image1|>",
                             "close":"</imgcaption>"};
     toolbar[toolbar.length] = {"type":"format",
                             "title":"Adds an ImageReference tag",
                             "icon":"../../plugins/imagereference/refbutton.png",
                             "key":"",
                             "open":"<imgref ",
                             "sample":"image1",
                             "close":">"};
}
 
 
  addInitEvent(function(){checkImages();});
