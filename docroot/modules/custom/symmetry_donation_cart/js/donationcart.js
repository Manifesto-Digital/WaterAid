(function ($, Drupal) {

  if (!Drupal.donationcart) Drupal.donationcart = function(){};
  if (!Drupal.donationcart.classes) Drupal.donationcart.classes = function(){};

  Drupal.donationcart.classes.CartItem = jscLib.libFuncs.subclass(jscLib.system.extendedObject, "Donation Cart Item");
  Drupal.donationcart.classes.CartItem.create = function(param){
    var _proto = this.prototype;

    _proto.init = function(param){
      if (param.cart == null) throw new jscLib.system.ESystem( { message: "Cart item must be associated to a donation cart", sysObject: _proto.self() } );
      this.cart = param.cart;
      this.itemcode = param.itemcode;
      if (param.isSplit && param.isSplit == true){
        this.isSplit = true;
        this.breakup = new Object();
      } else {
        this.isSplit = false;
        this.breakup = null;
      }

      if (param.unit){
        this.unit = param.unit;
      }

      if (param.amount){
        this.amount = param.amount;
      }

      if (param.minamount){
        this.minamount = param.minamount;
      } else {
        this.minamount = 0;
      }
    };

    _proto.getCauseCode = function(){
      return this.itemcode;
    };

    _proto.getItemTotal = function(){
      var result = 0;
      if (this.isSplit && this.isSplit == true){
        for(var breakup in this.breakup){
          result += this.breakup[breakup] * this.getUnits();
        }
      } else {
        result = this.getAmount() * this.getUnits();
      }
      return result;
    };

    _proto.getAmount = function(){
      if (!this.amount) return 0;
      return this.amount;
    };

    _proto.getUnits = function(){
      if (!this.unit || this.unit == 0) return 1;
      else return this.unit;
    };

    _proto.updateUnits = function(unit){
      this.unit = unit;
      this.cart.notifyChange(this);
    };

    _proto.updateAmount = function(amount, amountid){
      var a = parseFloat(amount);
      if (a < 0 || isNaN(a)){
        this.amount = 0;
        this.cart.notifyChange(this);
        return false;
      }
      if (this.minamount && amount < this.minamount && amount > 0){
        this.amount = 0;
        this.cart.notifyChange(this);
        alertify.alert("Minimum amount is INR." + this.minamount.toFixed(2));
        return false;
      }
      if (this.isSplit){
        this.breakup[amountid] = amount;
      } else {
        this.amount = amount;
      }
      this.cart.notifyChange(this);
      //
      return true;
    };
  };

  Drupal.donationcart.classes.DonationCart = jscLib.libFuncs.subclass(jscLib.system.extendedObject, "Donation Cart");
  Drupal.donationcart.classes.DonationCart.create = function(param){
    var _proto = this.prototype;

    _proto.init = function(param){
      if (!param) { param = {}; }
      this.cartItems = new Object();
      if (param.onChangeEvent){
        this.onChangeEvent = param.onChangeEvent;
      } else {
        this.onChangeEvent = function(a, b) {};
      }
    };

    _proto.precheckUnit = function(o){
      var v = parseInt(o.value);
      if (isNaN(v) || v <= 0){
        jQuery(o).val("1");
        return true;
      }
      return true;
    };

    _proto.addCartItem = function(itemCode, item){
      this.cartItems[itemCode] = item;
    };

    _proto.getCartTotal = function(){
      var result = 0;
      for(var item in this.cartItems){
        var obj = this.cartItems[item];
        result += obj.getItemTotal();
      }

      return result;
    };

    _proto.notifyChange = function(cartitem){
      if (cartitem && this.onChangeEvent){
        this.onChangeEvent(this, cartitem);
      }
    };

    _proto.clearCart = function(){
      for(var item in this.cartItems){
        var obj = this.cartItems[item];
        obj.updateAmount(0);
      }
    };

    _proto.createCartItem = function(_itemcode, _issplit, _units, _amount, _minamount){
      var item = new Drupal.donationcart.classes.CartItem({
        cart: this,
        itemcode: _itemcode,
        isSplit: (_issplit == 'True'),
        unit: _units,
        amount: _amount,
        minamount: _minamount
      });
      this.addCartItem(_itemcode, item);
    };

    _proto.deserialize = function(){
      result = "";
      for(var item in this.cartItems){
        var obj = this.cartItems[item];
        var total = obj.getItemTotal();
        if (total > 0){
          if (obj.isSplit){
            for(var breakup in obj.breakup){
              result += obj.getCauseCode();
              result += "," + obj.getUnits();
              result += "," + obj.breakup[breakup];
              result += "," + breakup;
              result += "#";
            }
          } else {
            result += obj.getCauseCode();
            result += "," + obj.getUnits();
            result += "," + obj.getAmount();
          }
          result += "#";
        }
      }
      return result;
    };

    _proto.submit = function(paymentType, campaignCode, pageIdentifier){
      if (pageIdentifier == undefined || pageIdentifier == null) { pageIdentifier = ""; }
      if (campaignCode == undefined || campaignCode == null) { campaignCode = ""; }
      if (!paymentType) { paymentType = "CC"; }
      var frm = $('<form />', { method: 'POST' }).append(
        $('<input />', { id: 'CauseDetails', name: 'CauseDetails', type: 'hidden', value: this.deserialize() }),
        $('<input />', { id: 'PaymentType', name: 'PaymentType', type: 'hidden', value: paymentType }),
        $('<input />', { id: 'CampaignCode', name: 'CampaignCode', type: 'hidden', value: campaignCode }),
        $('<input />', { id: 'PageIdentifier', name: 'PageIdentifier', type: 'hidden', value: pageIdentifier }),
        $('<input />', { id: 'SourceURL', name: 'SourceURL', type: 'hidden', value: document.location.href }),
        $('<input />', { id: 'ReferringURL', name: 'ReferringURL', type: 'hidden', value: document.referrer })
      );

      var p = frm.serializeArray();
      $.ajax({
        url : "//india.wateraid.org/submit",
        type: "POST",
        data : p,
        dataType: "JSON",
        success:function(data, textStatus, jqXHR)
        {
          if (data.Sucessfull){
            location.href = "//india.wateraid.org/process/" + data.Message;
          } else {
            alert(data.Message);
          }
        },
        error: function(jqXHR, textStatus, errorThrown)
        {
          //if fails
        }
      });
    };
  };

})(jQuery, Drupal);
