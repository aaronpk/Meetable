(function() {

var maxHeight = 220;
var margin = 4;

var images;
var dimensions = {};
var containerWidth;

document.addEventListener("DOMContentLoaded", function(event) {

  debugLayoutMessage("DOMContentLoaded");

  images = document.querySelectorAll(".photo-album img");
  for(var i=0; i<images.length; i++) {
    if(!images[i].complete) {
      images[i].addEventListener("load", imageFinishedLoading);
    }
  }
  startLayout();

});

var lastContainerWidth = null;

// This prevents the layout loop from running continuously while the window is being resized
var resizeTimer = null;
window.onresize = function() {
  if(resizeTimer != null) {
    clearTimeout(resizeTimer);
  }
  resizeTimer = setTimeout(startLayout, 50);
}

var waitingFor = null;
var waitingForIndex = 0;

var currentRowWidth = 0;
var currentRowImages = [];

function debugLayoutMessage(msg) {
  // console.log(msg);
}

function startLayout() {
  if(document.getElementsByClassName('photo-album').length > 0) {
    document.getElementsByClassName('photo-album')[0].style.display = 'block';
    containerWidth = lastContainerWidth = document.getElementsByClassName('photo-album')[0].clientWidth;
    currentRowImages = [];
    currentRowWidth = 0;
    waitingFor = null;
    waitingForIndex = 0;
    continueLayout();
  }
}

function continueLayout() {

  debugLayoutMessage("images.length = " + images.length);
  debugLayoutMessage("query length = " + document.querySelectorAll(".photo-album img").length);
  debugLayoutMessage("Continuing layout. We were previously waiting for "+waitingForIndex+"/"+images.length+" to load");

  // Start at the previous image that had not been loaded yet
  for(var i=waitingForIndex; i<images.length; i++) {
    // Start layout out images until the next unloaded image is reached
    if(images[i].complete) {
      debugLayoutMessage("Image "+i+" loaded");
      layOutImageNumber(i);
    } else {
      debugLayoutMessage("Image "+i+" is not yet loaded. Blocking on it.");
      waitingForIndex = i;
      waitingFor = images[i];
      return;
    }
  }

  // If there are any images left in the current row, resize them now

  // Allow the last row to be any height in order to fill the width
  if(currentRowImages.length > 0) {
    debugLayoutMessage("Finishing up the last row");
    var rowWidth = 0;
    for(var i=0; i<currentRowImages.length; i++) {
      rowWidth += currentRowImages[i].clientWidth;
    }
    var scale = (containerWidth - (margin * (currentRowImages.length+1))) / rowWidth;
    // Check if scaling any of the images would result in a super tall row
    var fillWidth = true;
    for(var i=0; i<currentRowImages.length; i++) {
      if(Math.floor(scale * currentRowImages[i].clientHeight) > maxHeight * 1.2) {
        fillWidth = false;
      }
    }
    if(fillWidth) {
      for(var i=0; i<currentRowImages.length; i++) {
        var w = Math.floor(scale * currentRowImages[i].clientWidth);
        var h = Math.floor(scale * currentRowImages[i].clientHeight);
        debugLayoutMessage("w: "+w+" h: "+h);
        setImageDimensions(currentRowImages[i], w, h);
      }
    } else {
      // Use the max height as the target
      if(currentRowImages.length > 0) {
        debugLayoutMessage("Finishing up the last row");
        for(var i=0; i<currentRowImages.length; i++) {
          var w = Math.floor(maxHeight * (currentRowImages[i].naturalWidth / currentRowImages[i].naturalHeight));
          debugLayoutMessage("w: "+w+" h: "+maxHeight);
          setImageDimensions(currentRowImages[i], w, maxHeight);
        }
      }
    }
  } else {
    debugLayoutMessage("currentRowImages.length = "+currentRowImages.length);
  }

  debugLayoutMessage("Layout complete");
}

function imageFinishedLoading(evt) {
  // If the image that finished loading was the one we were waiting for, continue laying out images
  if(evt.target == waitingFor) {
    debugLayoutMessage("The image we were waiting for finished loading");
    continueLayout();
  }
}

function calculateHeightOfCurrentRow() {
  // Calculate the height of the row if all the current images were rendered in the row
  var availableWidth = containerWidth - ((currentRowImages.length+1) * margin);
  var aspect = 0;
  for(var i=0; i<currentRowImages.length; i++) {
    aspect += parseInt(currentRowImages[i].naturalWidth) / parseInt(currentRowImages[i].naturalHeight);
  }
  debugLayoutMessage("Aspect of this row: "+aspect+ " Height: "+(availableWidth / aspect));
  return availableWidth / aspect;
}

function setImageDimensions(img, w, h) {
  img.removeAttribute("height");
  img.setAttribute("style", "height:"+h+"px; width:"+w+"px");
  img.calculatedWidth = w;
  img.calculatedHeight = h;
}

function layOutImageNumber(n) {
  var img = images[n];
  var iw = img.naturalWidth;
  var ih = img.naturalHeight;

  // Add this image to the row
  debugLayoutMessage("Adding image "+n+" to the current row");
  currentRowImages.push(img);

  var rowHeight = calculateHeightOfCurrentRow();
  // If the current row is under the max height, set the dimensions of the images in the row
  if(rowHeight < maxHeight) {
    debugLayoutMessage("Row height ("+rowHeight+") is under max height ("+maxHeight+")");
    debugLayoutMessage("Setting the heights for the current row");

    for(var i=0; i<currentRowImages.length; i++) {
      var w = currentRowImages[i].naturalWidth;
      var h = currentRowImages[i].naturalHeight;

      var newH = Math.floor(rowHeight);
      var newW = Math.floor(newH * (w / h));
      debugLayoutMessage("img: "+i+" w:"+newW+" h:"+newH);

      setImageDimensions(currentRowImages[i], newW, newH);
    }

    // Since we used the floor of the scaled result, we might need to add back a few pixels to make the spacing work
    var rowWidth = margin * (currentRowImages.length + 1);
    for(var i=0; i<currentRowImages.length; i++) {
      rowWidth += currentRowImages[i].calculatedWidth;
    }
    debugLayoutMessage("This row calculated to be "+rowWidth);
    if(rowWidth < containerWidth) {
      var extraSpace = containerWidth - rowWidth;
      var pos = 0;
      while(extraSpace > 0) {
        setImageDimensions(currentRowImages[pos], currentRowImages[pos].calculatedWidth+1, currentRowImages[pos].calculatedHeight);
        pos = (pos + 1) % currentRowImages.length;
        extraSpace--;
      }
    }

    // Then start a new row
    debugLayoutMessage("Starting a new row");
    currentRowImages = [];
    currentRowWidth = margin;

  }

}

})();
