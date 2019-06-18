"use strict";$(function(){function a(){var a=Number.isFinite,c=Number.isInteger,d=Number.isNaN,e=+b.val();return d(e)||!c(e)||1>e||!a(e)?(b.addClass("cbtb-invalid-input"),!1):(b.removeClass("cbtb-invalid-input"),!0)}var b=$("#cbtb_servings_field");b.blur(function(){a()}).on("keyup",function(b){13===b.keyCode&&a()})});

//# sourceMappingURL=servings-meta-compiled.js.map