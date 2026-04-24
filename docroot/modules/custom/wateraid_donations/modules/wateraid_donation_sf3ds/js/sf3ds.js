/**
 * @file
 * Javascript behaviors for webform GMO elements.
 */

(function ($, Drupal, drupalSettings, Multipayment, once) {

  Drupal.behaviors.sf3ds = {

    attach: function (context, settings) {
      let selector_cardno = '#edit-cardnumber';
      let selector_cardholder = '#edit-cardholder'
      let selector_token = '#edit-pttoken';
      let selector_expire_month = '#edit-month';
      let selector_expire_year = '#edit-year';
      let selector_security_code = '#edit-security-code'
      let shop_id ='tshop00015144';
      let modalDisplayed = false;
      let modalConfirmed = false;

      const form = document.querySelector('.webform-submission-form');

      const onSubmit = (e) => {
        e.preventDefault();

        // Do nothing if the modal is open.
        if (modalDisplayed && !modalConfirmed) {
          return false;
        }

        const submissionData = getSubmissionData(form);

        if (!submissionData) {

          // Let the webform validation handle the errors.
          return false;
        }

        const token = document.querySelector('#edit-pttoken');

        if (token) {
          if (isSf3ds() && !token.value) {
            getPaymentToken();
            return false;
          }
          else {
            if (!modalDisplayed && !modalConfirmed) {
              displayReviewModal();
              return false;
            }
          }
        }

        return false;
      };

      if (form) {
        form.addEventListener("submit", onSubmit);
      }

      $('.webform-submission-form').submit(function () {
        return;

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

      function isSf3ds() {
        const payment_methods = document.querySelectorAll('input.wa-donation-method-selection');
        var result = false;

        if (payment_methods) {
          payment_methods.forEach(payment_method => {
            if (payment_method.checked) {
              if (payment_method.value === 'sf3ds') {
                result = true;
              }
            }
          });
        }

        return result;
      }

      function displayReviewModal() {
        modalDisplayed = true;

        // Get input data here.
        const submissionData = getSubmissionData();

        // Create modal markup.
        const modal = document.createElement('div');
        modal.classList.add('node--type-webform', 'jp-donation-modal');
        const modalTitle = document.createElement('h2');
        modalTitle.innerText = Drupal.t('Please review your form submission');
        modal.append(modalTitle);

        // Modal content structure.
        const structure = [
          {
            label: Drupal.t('❶ 寄付金額'),
            fields: {
              Amount: Drupal.t('寄付金額'),
            }
          },
          {
            label: Drupal.t('❷ お名前・ご連絡先'),
            fields: {
              IndCorp: Drupal.t('個人／法人'),
              CorpName: Drupal.t('法人名'),
              CorpNameJp: Drupal.t('法人名（フリガナ）'),
            }
          },
          {
            label: Drupal.t('お名前'),
            fields: {
              LastNm: Drupal.t('姓'),
              FirstNm: Drupal.t('名'),
            }
          },
          {
            label: Drupal.t('お名前（フリガナ）'),
            fields: {
              LastNmJp: Drupal.t('セイ'),
              FirstNmJp: Drupal.t('メイ'),
            }
          },
          {
            label: Drupal.t('ご住所'),
            fields: {
              PostCode: Drupal.t('郵便番号'),
              Prefecture: Drupal.t('都道府県'),
              City: Drupal.t('市区町村'),
              Street: Drupal.t('番地・建物名'),
              Email: Drupal.t('メールアドレス'),
              Enewsletter: '',
              Phone: Drupal.t('電話番号'),
            }
          },
          {
            label: Drupal.t('❸ お支払い情報'),
            fields: {
              Method: Drupal.t('決済方法'),
              Receipt: Drupal.t('領収書発行'),
              Memo: Drupal.t('備考欄'),
            }
          },
          {
            label: Drupal.t('❹ プライバシーポリシー'),
            fields: {
              Agree: Drupal.t('プライバシーポリシーをご確認いただき、同意をお願いいたします。'),
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
        buttonWrapper.classList.add('donation-modal__button-wrapper', 'button__wrapper', 'button__wrapper--primary');
        cancelButton.setAttribute('type', 'button');
        cancelButton.setAttribute('id', 'cancel-button')
        const cancelLabel = document.createElement('label');
        cancelLabel.innerText = Drupal.t('Amend');
        cancelLabel.classList.add('button__input-button-wrapper', 'button', 'button--secondary', 'button--light')
        cancelLabel.append(cancelButton);
        confirmButton.setAttribute('type', 'button');
        const confirmLabel = document.createElement('label');
        confirmLabel.innerText = Drupal.t('Submit');
        confirmLabel.classList.add('button__input-button-wrapper', 'button', 'button--primary', 'button--light')
        confirmLabel.append(confirmButton);
        buttonWrapper.append(cancelLabel, confirmLabel);
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
        const form = document.querySelector('.webform-submission-form');
        const inputs = form.querySelectorAll("input");
        let submissionData = true;

        if (inputs) {
          inputs.forEach(input => {

            if (input && input.required) {
              if (!input.value) {
                submissionData = false;
              }
            }
          });
        }

        if (submissionData) {
          submissionData = {};

          const individual = form.querySelector('.form-item-individual-corporate');

          submissionData.indCorp = individual.querySelector('input:checked').value;

          submissionData.FirstNm = form.querySelector('#edit-first-name').value;
          submissionData.LastNm = form.querySelector('#edit-last-name').value;
          submissionData.FirstNmJp = form.querySelector('#edit-first-name-in-japanese').value;
          submissionData.LastNmJp = form.querySelector('#edit-last-name-in-japanese').value;
          submissionData.Email = form.querySelector('#edit-email-email').value;
          submissionData.PostCode = form.querySelector('#edit-postcode').value;
          submissionData.Prefecture = form.querySelector('#edit-prefecture').value;
          submissionData.City = form.querySelector('#edit-city').value;
          submissionData.Street = form.querySelector('#edit-street').value;
          submissionData.Phone = form.querySelector('#edit-phone').value;
          submissionData.Memo = form.querySelector('#edit-memo').value;
          submissionData.ENewsletter = (form.querySelector('#edit-e-newsletter').value === "1") ? Drupal.t('このメールアドレスにメールマガジンを配信する') : '';
          submissionData.Agree = Drupal.t('同意する');

          if (submissionData.indCorp !== 'individual') {
            submissionData.CorpName = form.querySelector('#edit-edit-corporate-name').value;
            submissionData.CorpNameJp = form.querySelector('#edit-corporate-name-in-japanese').value;
          }

          const amountButtons = form.querySelector('#edit-donation-amount-amount-one-off-amounts-buttons');
          const amount = amountButtons.querySelector('input:checked').value;

          submissionData.Amount = new Intl.NumberFormat("ja-JP", { style: "currency", currency: "JPY" }).format(amount);

          const receipts = form.querySelector('.form-item-receipt')
          const receipt = receipts.querySelector('input:checked').value;

          submissionData.Receipt = (receipt === "yes") ? Drupal.t('希望する') : Drupal.t('希望しない');
          submissionData['method'] = Drupal.t('クレジットカード');
        }

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

})(jQuery, Drupal, drupalSettings, Multipayment, once);
