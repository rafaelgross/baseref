#!/usr/bin/env python3
"""Recreate RSL / PRISMA-SLR logo as SVG → PNG"""
import cairosvg

svg = """<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 600" width="800" height="600">
  <defs>
    <!-- Background white -->
    <radialGradient id="bgGrad" cx="50%" cy="50%" r="70%">
      <stop offset="0%" stop-color="#ffffff"/>
      <stop offset="100%" stop-color="#f0f2f5"/>
    </radialGradient>

    <!-- Blue ribbon gradient (dark → bright blue) -->
    <linearGradient id="ribbonTop" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%"   stop-color="#1a3a8f"/>
      <stop offset="40%"  stop-color="#2255cc"/>
      <stop offset="80%"  stop-color="#1e4db5"/>
      <stop offset="100%" stop-color="#0f2d7a"/>
    </linearGradient>
    <linearGradient id="ribbonMid" x1="0%" y1="0%" x2="100%" y2="0%">
      <stop offset="0%"   stop-color="#1e4db5"/>
      <stop offset="50%"  stop-color="#3a72e8"/>
      <stop offset="100%" stop-color="#1a3a8f"/>
    </linearGradient>
    <linearGradient id="ribbonShadow" x1="0%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%"   stop-color="#0d1f5c"/>
      <stop offset="100%" stop-color="#091440"/>
    </linearGradient>

    <!-- Crystal gradients -->
    <linearGradient id="crystalLeft" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%"   stop-color="#b8e4f9"/>
      <stop offset="60%"  stop-color="#5ab8e8"/>
      <stop offset="100%" stop-color="#2980b9"/>
    </linearGradient>
    <linearGradient id="crystalRight" x1="0%" y1="100%" x2="100%" y2="0%">
      <stop offset="0%"   stop-color="#d6f0fb"/>
      <stop offset="50%"  stop-color="#a8daf5"/>
      <stop offset="100%" stop-color="#7bc8ef"/>
    </linearGradient>
    <linearGradient id="crystalCenter" x1="0%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%"   stop-color="#e8f6fd"/>
      <stop offset="40%"  stop-color="#bde8f8"/>
      <stop offset="100%" stop-color="#4db3e8"/>
    </linearGradient>
    <linearGradient id="crystalDark" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%"   stop-color="#3a8fc4"/>
      <stop offset="100%" stop-color="#1a5c8a"/>
    </linearGradient>

    <!-- Letter shadow filter -->
    <filter id="letterShadow" x="-5%" y="-5%" width="115%" height="115%">
      <feDropShadow dx="2" dy="3" stdDeviation="3" flood-color="#00000033"/>
    </filter>
    <filter id="ribbonGlow" x="-10%" y="-10%" width="120%" height="120%">
      <feGaussianBlur stdDeviation="2" result="blur"/>
      <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
    </filter>
  </defs>

  <!-- Background -->
  <rect width="800" height="600" fill="url(#bgGrad)"/>

  <!-- ══════════════════════════════════════════
       LETTERS  R · S · L  (dark charcoal gray)
  ══════════════════════════════════════════ -->
  <g filter="url(#letterShadow)" font-family="'Arial Black','Impact','Franklin Gothic Heavy',sans-serif"
     font-weight="900" fill="#3a3a3a">

    <!-- R  (left, slightly larger) -->
    <text x="88" y="330" font-size="290" letter-spacing="-8">R</text>

    <!-- S  (center, slightly pulled left so ribbon overlaps) -->
    <text x="278" y="330" font-size="290" letter-spacing="-8">S</text>

    <!-- L  (right) -->
    <text x="520" y="330" font-size="290" letter-spacing="-8">L</text>
  </g>

  <!-- ══════════════════════════════════════════
       BLUE RIBBON — flowing S-curve over the letters
       Approximated with thick bezier paths
  ══════════════════════════════════════════ -->

  <!-- Main ribbon body — upper loop (curves up-right over S) -->
  <!-- shadow/depth layer first -->
  <path d="
    M 200,285
    C 230,210 320,155 400,140
    C 470,128 530,145 565,175
    C 600,205 608,240 590,265
    C 572,290 540,300 510,292
    C 480,284 455,262 450,240
    C 445,218 458,196 478,188
    C 498,180 525,185 540,200
    L 548,190
    C 530,172 498,165 473,175
    C 448,185 432,212 438,240
    C 444,268 472,294 505,302
    C 538,310 576,298 597,268
    C 618,238 610,196 577,163
    C 540,128 480,112 405,126
    C 330,140 235,198 200,280 Z"
    fill="#0c1e5e" opacity="0.5"/>

  <!-- upper ribbon main color -->
  <path d="
    M 195,278
    C 225,200 318,143 400,128
    C 472,115 535,134 572,166
    C 610,198 618,240 597,268
    C 576,296 538,308 505,300
    C 472,292 444,268 438,240
    C 432,212 448,185 473,175
    C 498,165 530,172 548,190
    C 548,190 540,182 522,180
    C 504,178 482,188 473,206
    C 464,224 470,248 485,262
    C 500,276 522,284 545,278
    C 568,272 586,254 592,232
    C 602,200 588,162 558,140
    C 522,115 462,108 400,122
    C 320,138 218,202 192,285 Z"
    fill="url(#ribbonTop)" filter="url(#ribbonGlow)"/>

  <!-- ribbon highlight strip (top edge shine) -->
  <path d="
    M 210,262
    C 245,195 335,148 410,135
    C 470,125 525,140 558,165
    L 545,155
    C 510,130 462,118 398,132
    C 320,148 228,200 195,272 Z"
    fill="#5580e8" opacity="0.6"/>

  <!-- lower ribbon loop — curves down and left under S -->
  <!-- shadow layer -->
  <path d="
    M 592,268
    C 610,290 610,330 590,362
    C 570,394 535,410 498,406
    C 461,402 430,376 420,345
    C 410,314 422,280 445,265
    C 462,253 484,252 500,260
    L 492,268
    C 478,262 460,264 448,274
    C 430,288 422,314 430,340
    C 438,366 462,386 492,390
    C 522,394 552,380 568,355
    C 584,330 584,296 568,273 Z"
    fill="#0c1e5e" opacity="0.5"/>

  <!-- lower ribbon main -->
  <path d="
    M 597,268
    C 618,292 618,338 595,372
    C 572,406 532,424 492,420
    C 452,416 418,388 406,354
    C 394,320 408,282 436,264
    C 456,250 480,248 500,258
    C 520,268 532,288 528,308
    C 524,328 508,344 490,346
    C 472,348 454,338 448,322
    C 442,306 450,288 464,280
    L 460,276
    C 442,286 432,308 438,330
    C 444,352 464,366 488,364
    C 512,362 532,344 536,320
    C 540,296 526,270 504,256
    C 482,242 452,242 430,258
    C 400,278 385,320 398,358
    C 411,396 450,428 494,432
    C 538,436 582,415 607,378
    C 632,341 632,292 610,265 Z"
    fill="url(#ribbonMid)"/>

  <!-- lower ribbon highlight -->
  <path d="
    M 600,270
    C 618,295 618,335 596,368
    L 606,360
    C 626,328 626,292 608,268 Z"
    fill="#5580e8" opacity="0.5"/>

  <!-- ribbon tail/end (curls left under R-S gap) -->
  <path d="
    M 195,278
    C 185,300 188,325 205,342
    C 222,359 250,362 272,352
    C 294,342 305,320 300,298
    L 292,302
    C 296,320 286,340 268,348
    C 250,356 228,350 216,336
    C 204,322 202,302 212,284 Z"
    fill="url(#ribbonShadow)"/>

  <!-- ══════════════════════════════════════════
       CRYSTAL / DIAMOND at top-right of S
  ══════════════════════════════════════════ -->
  <g transform="translate(490, 60) rotate(28)">
    <!-- Crystal body — multiple facets -->

    <!-- back/base (darkest) -->
    <polygon points="40,130 80,50 120,130" fill="#1a5c8a" opacity="0.9"/>

    <!-- left facet -->
    <polygon points="0,80 40,130 80,50" fill="url(#crystalLeft)"/>

    <!-- right facet -->
    <polygon points="80,50 120,130 155,75" fill="url(#crystalRight)"/>

    <!-- center top facet (brightest) -->
    <polygon points="40,0 80,50 120,0" fill="url(#crystalCenter)"/>

    <!-- left-top facet -->
    <polygon points="10,20 40,0 80,50 40,130" fill="#a8daf5" opacity="0.7"/>

    <!-- right-top facet -->
    <polygon points="80,50 120,0 148,25 120,130" fill="#7bc8ef" opacity="0.8"/>

    <!-- inner highlight (white shine) -->
    <polygon points="55,10 80,50 105,10" fill="white" opacity="0.5"/>
    <polygon points="60,55 80,50 75,75" fill="white" opacity="0.4"/>

    <!-- Crystal tip glow -->
    <circle cx="80" cy="0" r="5" fill="white" opacity="0.8"/>
  </g>

  <!-- ══════════════════════════════════════════
       PRISMA-SLR  wordmark
  ══════════════════════════════════════════ -->
  <text x="400" y="420"
        font-family="'Arial','Helvetica',sans-serif"
        font-weight="700"
        font-size="58"
        fill="#3a3a3a"
        text-anchor="middle"
        letter-spacing="4">PRISMA-SLR</text>

  <!-- ══════════════════════════════════════════
       Subtitle
  ══════════════════════════════════════════ -->
  <text x="400" y="462"
        font-family="'Arial','Helvetica',sans-serif"
        font-weight="700"
        font-size="18"
        fill="#1e4db5"
        text-anchor="middle"
        letter-spacing="1.5">SISTEMA PARA GESTÃO DE REVISÕES</text>
  <text x="400" y="488"
        font-family="'Arial','Helvetica',sans-serif"
        font-weight="700"
        font-size="18"
        fill="#1e4db5"
        text-anchor="middle"
        letter-spacing="1.5">SISTEMÁTICAS DA LITERATURA</text>

</svg>"""

with open("/tmp/rsl_logo.svg", "w") as f:
    f.write(svg)

cairosvg.svg2png(
    url="/tmp/rsl_logo.svg",
    write_to="/home/rafaelgross.pro.br/rsl/assets/img/logo-rsl.png",
    output_width=1200,
    output_height=900,
    background_color="white"
)
print("Logo saved.")
