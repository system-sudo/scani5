<!DOCTYPE html>
<html lang="en">
  <head>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap"
      rel="stylesheet"
    />
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Scani5 Email</title>
    <style type="text/css">
      body {
        margin: 0;
        padding: 0;
        min-width: 100% !important;
        background: #ffffff;
        color: #515151;
      }

      img {
        border: 0;
      }

      .email-button {
            background-color: #7A69EE;
            border:1px solid;
            border-radius:8px;
            font-weight:600;
            color:white !important;
            display:inline-block;
            font-family:sans-serif;
            font-size:16px;
            line-height:17px;
            text-align:center;
            text-decoration:none;
            padding: 10px 20px;
            border: none;
            -webkit-text-size-adjust:none;
            mso-hide:all;
            margin-top: 15px

        }

      /* Styles for responsive view */
      @media only screen and (max-width: 480px) {
        table.mob_tbl {
          max-width: 100%;
          width: 100%;
        }

        .bgimg {
          background-size: auto !important;
        }

        .mail-title {
          font-size: 16px !important;
          /* line-height: 34px !important; */
        }

        .text-dec {
          padding: 0px !important;
          border: 0px !important;
        }

        .banner_side {
          width: 70px !important;
          height: auto !important;
          padding-left: 0 !important;
          padding-right: 24px !important;
          text-align: right;
        }

        .content {
          padding: 20px 24px !important;
        }

        .footer-bottom td {
          display: inline-block;
          padding: 0 !important;
          width: 49%;
          text-align: center;
          margin-bottom: 16px;
        }

        .right-line {
          border-right: none !important;
        }
      }
    </style>
  </head>

  <body>
    <!-- Header section starts here -->
    <div align="center">
        <table
        width="100%"
        cellpadding="0"
        cellspacing="0"
        bgcolor="white"
        align="center"
        style="width: 100%; max-width: 565px; padding: 10px; margin: 0 auto"
        align="center"
      >
        <tr>
          <!-- Banner section starts here -->
        </tr>

        <tr>
          <td width="100%" align="center" style="padding: 10px 0">
            <!-- Header logo -->
            <a name="" href="{{ asset('images/scanify_logo.png') }}" target="_blank">
                <img width="169px" alt="[Image: Logo]" src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/scanify_logo.png'))) }}">

            </a>
          </td>
        </tr>
        <tr>
          <!-- Image as background pod starts below -->
          <td
            class="bgimg"
            valign="top"
            align="center"
            style="
              padding-top: 20px;
              background-repeat: no-repeat;
              background-size: contain;
              background-position: center;
              background-image: url('images/banner.svg');
            "
            background="images/banner.svg"
          >
            <!--[if gte mso 12]>
                                     <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:600px;">
                                     <v:fill type="frame" src="images/banner.svg" style="width:100%;"></v:fill>
                                     <v:textbox style="mso-fit-shape-to-text:true" inset="0,0,0,0">
                                         <div style="line-height:1px">
                                             <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
                                                 <tr>
                                                     <td height="141" align="center">
                                                         <![endif]-->
            <table
              align="left"
              border="0"
              cellpadding="0"
              cellspacing="0"
              style="
                -webkit-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
                font-family: Plus Jakarta Sans, sans-serif;
                -webkit-box-sizing: border-box;
                -moz-box-sizing: border-box;
                box-sizing: border-box;
                mso-table-lspace: 0pt;
                mso-table-rspace: 0pt;
                border-collapse: collapse;
                width: 100%;
                height: 40px;
              "
            >
              <tbody>
                <tr>
                  <td align="center" style="padding: 0 0 0 0">
                    <!-- <td valign="middle" align="center" width="50%" style="padding-left: 60px;"
                                              class="banner_side"> -->
                    <!-- Banner Logo -->

                    <a name="" href="{{ asset('images/regenerate-totp.png') }}" target="_blank">
                        <img alt="[Image: Regenerate]" src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/regenerate-totp.png'))) }}">

                    </a>

                  </td>
                </tr>
                <tr>
                  <td valign="middle" align="center" width="50%" class="title">
                    <div
                      style="
                        display: flex;
                        flex-wrap: wrap;
                        justify-content: space-between;
                      "
                      class="content"
                    >
                      <div class="">
                        <h3 style="margin: 0" align="left">Hello,</h3>
                        <p
                          class="mail-title"
                          style="
                            white-space: nowrap;
                            padding: 0;
                            margin: 0;
                            font-weight: 400;
                            color: #1c1f2a;
                            font-size: 14px;
                            font-family: Plus Jakarta Sans, sans-serif;
                            padding-top: 10px;
                          "
                        >
                          You have received a request to regenerate TOTP!
                        </p>
                      </div>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td align="center" style="margin-top: 17px">
                    <div>
                      <!--[if mso]>
                        <v:roundrect
                          xmlns:v="urn:schemas-microsoft-com:vml"
                          xmlns:w="urn:schemas-microsoft-com:office:word"
                          href="#"
                          style="
                            height: 36px;
                            v-text-anchor: middle;
                            width: 152px;
                          "
                          arcsize="50%"
                          strokecolor="#71757A"
                          fillcolor="#fff"
                        >
                          <w:anchorlock />
                          <center
                            style="
                              color: #71757a;
                              font-family: Helvetica, Arial, sans-serif;
                              font-size: 14px;
                              font-weight: bold;
                            "
                          >
                            View our partners
                          </center>
                        </v:roundrect>
                      <![endif]-->
                      <a
                        href="https://www.google.co.in/"
                        class="email-button text-secondary"
                      >
                        Regenerate TOTP
                      </a>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
            <!--[if gte mso 12]>
                                                     </td>
                                                 </tr>
                                             </table>
                                         </div>
                                     </v:textbox>
                                     </v:rect>
                                 <![endif]-->
          </td>
          <!-- Image as background pod ends above -->
        </tr>
        <!-- Banner section ends here -->

        <!-- Description section starts here -->
        <tr>
          <td class="content">
            <table class="mob_tbl" cellspacing="0" cellpadding="0" align="center">
              <tbody>
                <!-- Description content -->
                <div
                  style="
                    display: flex;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    margin-top:17px;
                  "
                >
                  <div
                    style="
                      display: flex-column;
                      flex-wrap: wrap;
                      gap:15px;

                    "
                  >
                    <div style="display: flex" class="">
                        <a name="" href="{{ asset('images/user.png') }}" target="_blank">
                            <img style="margin-top: 12px" alt="[Image: Regenerate]" src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/user.png'))) }}">

                        </a>
                      <p
                        style="
                          font-weight: 500;
                          color: #1c1f2a;
                          font-size: 14px;
                          font-family: Plus Jakarta Sans, sans-serif;
                         margin-left: 6px;
                         padding-top:3px;

                        "
                      >
                        udhaya_sq1_admin
                      </p>
                    </div>
                    <div style="display: flex" class="">
                        <a name="" href="{{ asset('images/org.png') }}" target="_blank">
                            <img style="margin-top: 12px" alt="[Image: Regenerate]" src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/org.png'))) }}">

                        </a>
                      <p
                        style="
                          font-weight: 400;
                          color: #1c1f2a;
                          font-size: 14px;
                          font-family: Plus Jakarta Sans, sans-serif;
                         margin-left: 10px;

                        "
                      >
                        sq1 security
                      </p>
                    </div>
                  </div>

                  <div
                    class=""
                    style="
                      display: flex-column;
                      flex-wrap: wrap;
                      justify-content: space-between;
                    "
                  >
                    <div style="display: flex" class="">
                       <a name="" href="{{ asset('images/mail.png') }}" target="_blank">
                            <img style="margin-top: 12px" alt="[Image: Regenerate]" src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/mail.png'))) }}">

                        </a>
                      <p
                        style="
                          font-weight: 400;
                          color: #1c1f2a;
                          font-size: 14px;
                          font-family: Plus Jakarta Sans, sans-serif;
                          margin-left: 10px;
                        "
                      >
                        udhayakumar.g+sq-1admin@sq1.security
                      </p>
                    </div>
                    <div style="display: flex" class="">
                      <a name="" href="{{ asset('images/date.png') }}" target="_blank">
                            <img style="margin-top: 12px" alt="[Image: Regenerate]" src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/date.png'))) }}">

                        </a>
                      <p
                        style="
                          font-weight: 400;
                          color: #bdbdbd99;
                          font-size: 14px;
                          font-family: Plus Jakarta Sans, sans-serif;
                          margin-left: 10px;
                        "
                      >
                        Feb 4 2025 05:52 PM
                      </p>
                    </div>
                  </div>
                </div>
                <tr>
                  <td
                    align="left"
                    style="
                      padding-top: 17px;
                      padding-bottom: 17px;
                      font-weight: 400;
                      font-size: 14px;
                      font-family: Plus Jakarta Sans, sans-serif;
                      line-height: 16px;
                      color: #1c1f2a;
                    "
                  >
                    If you have any questions or need further information, please
                    do not hesitate to contact us at to
                    <a href="#" style="color: #7a69ee">support@sq1.security</a>
                  </td>
                </tr>
                <tr></tr>

                <tr>
                  <td
                    style="
                      font-size: 14px;
                      font-family: Plus Jakarta Sans, sans-serif;
                      line-height: 18px;
                      color: #1c1f2a;
                      padding-bottom: 10px;
                    "
                  >
                    Regards, <br /><span
                      style="
                        font-weight: 700;
                        font-size: 14px;
                        font-family: Plus Jakarta Sans, sans-serif;
                        line-height: 18px;
                        color: #1c1f2a;
                        padding-bottom: 10px;
                      "
                      >scani5</span
                    >
                  </td>
                </tr>
              </tbody>
            </table>
          </td>
        </tr>
        <tr>
          <td
            valign="top"
            style="
              padding-top: 10px;
              font-size: 14px;
              font-family: Plus Jakarta Sans, sans-serif;
              line-height: 14px;
              font-weight: 400;
              color: rgba(111, 111, 111, 0.922);
            "
          >
            <div style="width: 100%; height: 42px; background-color: #d7d2ff">
                <a href="{{ asset('images/watermark-logo.png') }}">
                    <img alt="[Image: Banner Logo]" src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/watermark-logo.png'))) }}" style="float: right;margin: 10px;" align="center">
                </a>
            </div>
          </td>
        </tr>
      </table>
    </div>
  </body>
</html>
