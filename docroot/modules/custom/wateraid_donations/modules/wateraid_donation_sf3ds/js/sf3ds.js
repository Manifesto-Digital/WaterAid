/**
 * @file
 * Javascript behaviors for webform GMO elements.
 */

(function ($, Drupal, drupalSettings, Multipayment) {

  Drupal.behaviors.sf3ds = {

    attach: function (context, settings) {
      let selector_form = '#sf3ds-card-form';
      let selector_cardno = '#edit-cardnumber';
      let selector_cardholder = '#edit-cardholder'
      let selector_token = '#edit-pttoken';
      let selector_expire_month = '#edit-month';
      let selector_expire_year = '#edit-year';
      let selector_security_code = '#edit-security-code'
      let shop_id ='tshop00015144';
      let modalDisplayed = false;
      let modalConfirmed = false;

      $(selector_form).submit(function () {

        if ($(selector_token).val()) {
          if (!modalDisplayed && !modalConfirmed) {
            displayReviewModal();
            return false;
          }

          // Only proceed if modal inputs have been confirmed.
          if (!modalConfirmed) {
            return false;
          }

          return true;
        } else {
          getPaymentToken();
          return false;
        }
      });

      function displayReviewModal() {
        modalDisplayed = true;

        // Get input data here.
        const submissionData = getSubmissionData();

        // Create modal markup.
        const modal = document.createElement('div');
        const modalTitle = document.createElement('h2');
        modalTitle.innerText = Drupal.t('Please review your form submission');
        modal.append(modalTitle);

        // Modal content structure.
        const structure = [
          {
            label: Drupal.t('❶ 寄付金額'),
            fields: {
              amount: Drupal.t('寄付金額'),
            }
          },
          {
            label: Drupal.t('❷ お名前・ご連絡先'),
            fields: {
              indcorp: Drupal.t('個人／法人'),
              corpname: Drupal.t('法人名'),
              corpnamejp: Drupal.t('法人名（フリガナ）'),
            }
          },
          {
            label: Drupal.t('お名前'),
            fields: {
              lastnm: Drupal.t('姓'),
              firstnm: Drupal.t('名'),
            }
          },
          {
            label: Drupal.t('お名前（フリガナ）'),
            fields: {
              lastnmjp: Drupal.t('セイ'),
              firstnmjp: Drupal.t('メイ'),
            }
          },
          {
            label: Drupal.t('ご住所'),
            fields: {
              postcode: Drupal.t('郵便番号'),
              prefecture: Drupal.t('都道府県'),
              city: Drupal.t('市区町村'),
              street: Drupal.t('番地・建物名'),
              email: Drupal.t('メールアドレス'),
              enewsletter: '',
              phone: Drupal.t('電話番号'),
            }
          },
          {
            label: Drupal.t('❸ お支払い情報'),
            fields: {
              method: Drupal.t('決済方法'),
              receipt: Drupal.t('領収書発行'),
              memo: Drupal.t('備考欄'),
            }
          },
          {
            label: Drupal.t('❹ プライバシーポリシー'),
            fields: {
              agree: Drupal.t('プライバシーポリシーをご確認いただき、同意をお願いいたします。'),
            }
          }
        ];

        structure.forEach(function (fieldset) {
          const sectionHeading = document.createElement('h3');
          sectionHeading.innerText = fieldset.label;
          const list = document.createElement('dl');
          const fieldSetData = Object.entries(fieldset.fields);

          fieldSetData.forEach(function (field) {
            if (submissionData[field[0]]) {
              const term = document.createElement('dt');
              const description = document.createElement('dd');
              term.innerText = field[1];
              description.innerText = submissionData[field[0]];
              description.classList.add(field[0]);
              list.append(term, description);
            }
          });

          modal.append(sectionHeading, list);
        });

        // Create submit and cancel buttons.
        const buttonWrapper = document.createElement('div');
        const cancelButton = document.createElement('button');
        const confirmButton = document.createElement('button');
        buttonWrapper.classList.add('donation-modal__button-wrapper');
        cancelButton.setAttribute('type', 'button');
        cancelButton.innerText = Drupal.t('Amend');
        confirmButton.setAttribute('type', 'button');
        confirmButton.innerText = Drupal.t('Submit');
        buttonWrapper.append(cancelButton, confirmButton);
        modal.append(buttonWrapper);

        // Open modal.
        const options = {
          width: '90%',
          classes: {
            'ui-dialog': 'donation-modal'
          }
        };
        const drupalDialog = Drupal.dialog(modal, options);
        drupalDialog.show();

        // Set up cancel event handler, which allows form to be amended.
        cancelButton.addEventListener('click', function () {
          drupalDialog.close();
          modalDisplayed = false;
        });

        // Set up confirm event handler, will allows form to be submitted.
        confirmButton.addEventListener('click', function () {
          drupalDialog.close();
          modalDisplayed = false;
          modalConfirmed = true;
          $(selector_form).submit();
        });
      }

      function getSubmissionData() {
        const $submissionFields = $('input[data-submission-value]');
        const submissionData = {};

        $submissionFields.each(function (i) {
          submissionData[$(this).data('submission-value')] = $(this).val();
        });

        // Tweak the data for easy output.
        submissionData.agree = (submissionData.agree === "1") ? Drupal.t('同意する') : '';
        submissionData.amount = new Intl.NumberFormat("ja-JP", { style: "currency", currency: "JPY" }).format(submissionData.amount);
        submissionData.indcorp = (submissionData.indcorp === "individual") ? Drupal.t('個人') : Drupal.t('法人');
        submissionData.receipt = (submissionData.receipt === "yes") ? Drupal.t('希望する') : Drupal.t('希望しない');
        submissionData.enewsletter = (submissionData.enewsletter === "1") ? Drupal.t('このメールアドレスにメールマガジンを配信する') : '';
        submissionData['method'] = Drupal.t('クレジットカード');

        return submissionData;
      }

      function getPaymentToken() {
        var shopId = shop_id;
        var cardno = $(selector_cardno).val();
        var expire = $(selector_expire_year).val() + $(selector_expire_month).val();
        var securitycode = $(selector_security_code).val();
        var holdername = $(selector_cardholder).val();

        Multipayment.init(shopId); // API key for using token
        Multipayment.getToken(
          {
            cardno: cardno, // Card number obtained from the merchant's purchase form
            expire: expire, // Card expiration date obtained from the merchant's purchase form
            securitycode: securitycode, // Security code obtained from the merchant's purchase form
            holdername: holdername // Cardholder name obtained from the merchant's purchase form
          }, function (response) {
            if (response.resultCode != "000") {
              window.alert(Drupal.t('An error occurred during purchase processing'));
            } else {
              $(selector_token).val(response.tokenObject.token);
              $(selector_form).submit();
            }
          } // JavaScript function to be executed after token is obtained
        );
      }
    }
  }

})(jQuery, Drupal, drupalSettings, Multipayment);
