/*
    Author: Sriram Ambuga N
    Copyright 2006-2008 Symmetry Infotech, Bangalore

    This file defines the base classes that assist in communicating and maintaing the presentation windows.

    -- (Symmetry JS Library note) System.js is the original file name - adapted to DMSHIVE.js
    -- De-compressed version.
*/

if (!window.jscLib){
  var jscLib = function(){
    return "";
  }

  jscLib.version = {
    major: 1, minor: 0, patch: 0, flag: " Beta",
    revision: Number("$Rev: 0 $".match(/[0-9]+/)[0]),
    toString: function() {
      with (jscLib.version) {
        return major + "." + minor + "." + patch + flag + " (" + revision + ")";
      }
    }
  };

  jscLib.toString = function(){
    return "Symmetry Javascript Library [" + this.version + "]";
  }
}

if (!jscLib.system) jscLib.system = function(){}
if (!jscLib.types) jscLib.types = function(){}
if (!jscLib.constants) jscLib.constants = function(){}
if (!jscLib.libFuncs) jscLib.libFuncs = function(){}
if (!jscLib.utilFuncs) jscLib.utilFuncs = function(){}
if (!jscLib.global) jscLib.global = function(){}
if (!jscLib.debug) jscLib.debug = function(){}

/********************************
 Types definitions
 ********************************/
jscLib.types.scope = {
  isPublished: 1,
  isPublic: 2
};

jscLib.types.dataTypes = {
  string: 1,
  integer: 2,
  bool: 3,
  object: 4,
  collection: 5,
  libObject: 6
}

/********************************
 Utility functions
 ********************************/
jscLib.utilFuncs.toCamelCase = function(){
  var retVal = arguments[0];
  //
  retVal = retVal.substr(0,1).toLowerCase() + retVal.substr(1);
  for (var counter=1; counter < arguments.length; counter++){
    retVal += arguments[counter].substr(0,1).toUpperCase() + arguments[counter].substr(1);
  }
  return retVal;
};

/********************************
 Library functions
 ********************************/
jscLib.libFuncs.subclass = function(fnClass, cName){
  if (fnClass == null){
    fnClass = jscLib.libFuncs.funcTemplate;
  }
  var classArray = new Array();

  var create = function(cls, args){
    if (cls.__superclass){
      classArray.push(cls.__superclass);
      create(cls.__superclass, args);
    };

    if (args){
      if (cls.create) cls.create(args[0]);
    } else {
      if (cls.create) cls.create();
    }
  }

  var constructor = function(){
    if (arguments[0] == '###skip###') { return };
    //
    create(constructor, arguments);
    this.Parameters = arguments;
    this.setSelf(this);

    if (this.init) { if (arguments.length > 0) { this.init(arguments[0]); } else { this.init(); } }
  }

  // copy everything from the base to the current
  // constructor.
  for (i in fnClass){
    constructor[i] = fnClass[i];
  }

  // Create a new prototype object here. Else we would be
  // toying around with the base class prototype itself !!
  constructor.prototype = new fnClass('###skip###');
  constructor.__superclass = fnClass;
  constructor.isLibClass = true;

  // Add class name to both function and to the instance hmm - the prototype i.e.
  constructor.className = cName;
  constructor.prototype._self = null;
  constructor.prototype.isLibClass = true;
  constructor.prototype.className = cName;
  constructor.prototype.classArray = classArray;
  constructor.prototype.thisConstructor = constructor;
  return constructor;
}

jscLib.libFuncs.funcTemplate = function(){};
jscLib.libFuncs.funcTemplate.className = "Root";
jscLib.libFuncs.funcTemplate.__superclass = null;

/************************************************************************************/
/***************************                               **************************/
/***************************       Namespace: System       **************************/
/***************************                               **************************/
/************************************************************************************/

/********************************** Prototype: Object ********************************/
jscLib.system.object = jscLib.libFuncs.subclass(null, "Object");
var iterator = 0;
jscLib.system.object.create = function(){
  var _proto = this.prototype;
  var _self = null;

  _proto.setSelf = function(obj){
    this._self = obj;
  }

  _proto.self = function(){
    return this._self;
  }

  _proto.toString = function(){
    if (this.className){
      return "[object " + this.className + "]";
    } else {
      return "[object Root]";
    }
  }

  _proto.classParent = function(){
    if (this.classArray && this.classArray.length > 0){
      return this.classArray[0];
    }

    return null;
  }

  _proto.inherited = function(method, args){
    // Stack variable keeps track of the incrementing class position for the method.
    // Required if the inherited class calls inherited again.
    // Since apply() is used, the context will be the same as the derived object. Hence,
    // inherited in the parent class would trigger the inherited of the derived only.
    var stack = this['@@stack' + method] ? this['@@stack' + method] : 0;
    var execFunction = function(){};
    execFunction.prototype.execute = new Function(
      'try{ return [this.parentClass.prototype.' + method + '.apply(this.$caller, [this.args]), true]} catch(e) {return [null, false, e]}');

    // Locate the class that is required to be processed upon.
    // Use the stack variable here to locate.
    var cls = this.classParent();
    for (var cnt = 0; cnt < stack; cnt++){
      if (cls.__superclass){
        cls = cls.__superclass;
      } else {
        return false;
      }
    }

    execFunction.prototype.parentClass = cls;
    execFunction.prototype.$caller = this;
    execFunction.prototype.args = args;
    //
    mthd = new execFunction();

    // Update the stack variable before execution
    this['@@stack' + method] = ++stack;
    returnVal = mthd.execute();

    // Decrement the stack variable after execution.
    this['@@stack' + method] = --stack;

    // if the stack has reached 0 then delete the stack..
    // alternate mechanism is also to check if cls = this.classParent(); if both are
    // same then delete the stack. Currently this works and since the execute() is
    // within a try..catch, it should not throw up any surprises.
    if (stack == 0){
      delete this['@@stack' + method];
    }
    if (returnVal[1]) { return returnVal[0] } else { return returnVal[2] };
  }

  _proto.classNameIs = function(name){
    return this.className == name;
  }

  _proto.dispatch = function(message){
    // First find out from the list of registered message handlers and if none is found then
    // send it across to default handler.
  }

  _proto.defaultHandler = function(message){
  }

  _proto.newInstance = function(){
    return new this.thisConstructor;
  }

  _proto.clone = function(){
    var retVal = this.newInstance;
    for (i in this){
      retVal[i] = this[i];
    }
    return retVal;
  }

  _proto.inheritsFrom = function(source){
    var compareTo = source;
    if (typeof source == "object") compareTo = source.constructor;
    if (this.thisConstructor == compareTo){
      return true;
    } else {
      for (var i=0;i < this.classArray.length; i++){
        if (this.classArray[i] == compareTo){
          return true;
        }
      }
      return false;
    }
  }
}

/******************************** Prototype: Extended Object *******************************/
/*
    SAMPLE GET FUNCTION WHICH CALLS THE GET IN THE CONTEXT OF 'SELF' AND NOT
    THE THIS..
*/
function propFunction(get, con){
  var method = get;
  var context = con;
  this.execute = execute;

  function execute(args){
    try{
      if (args){
        return method.apply(context, [args]);
      } else {
        return method.apply(context, []);
      }
    } catch(e) {
      alert(e);
    }
  }
};

/*
new getFunction(getter, this.self());
    this[p].getFunc = this[p].getValue;
    this[p].getValue = this[p].getValue.execute;

*/

jscLib.system.extendedObject = jscLib.libFuncs.subclass(jscLib.system.object, "Extended Object");
jscLib.system.extendedObject.create = function(){
  var _proto = this.prototype;
  var utils = jscLib.utilFuncs;

  _proto.defineProperty = function(obj, p, getter, setter){
    this[p] = new Object;
    /*
    if (setter){
        this[p].setValue = setter;
    } else {
        this[p].setValue = function(){
            throw new jscLib.system.ESystem( { message: "Read only property and cannot be modified", sysObject: _proto.self() } );
        }
    };
    //this[p].getValue = getter;*/

    if (setter){
      this[p].setter = setter;
      this[p].setValue = new propFunction(setter, obj);
      this[p].setFunc = this[p].setValue;
      this[p].setValue = this[p].setValue.execute;

    } else {
      this[p].setValue = function(){
        throw new jscLib.system.ESystem( { message: "Read only property and cannot be modified", sysObject: this.self() } );
      }
    };
    this[p].getter = getter;
    this[p].getValue = new propFunction(getter, obj);
    this[p].getFunc = this[p].getValue;
    this[p].getValue = this[p].getValue.execute;
  };
}

/******************************** Prototype: Exception *******************************/
jscLib.system.Exception = jscLib.libFuncs.subclass(jscLib.system.object, "Exception");
jscLib.system.Exception.create = function(param){
  var _proto = this.prototype;

  _proto.toString = function(){
    if (this.className){
      return param.message;
    }
  }
}

jscLib.system.ESystem = jscLib.libFuncs.subclass(jscLib.system.Exception, "System Exception");
jscLib.system.ESystem.create = function(param){
  var _proto = this.prototype;

  _proto.init = function(){
  }

  _proto.toString = function(){
    if (this.className){
      return 'Message : ' + param.message + '\n' + 'Object :' + param.sysObject;
    }
  }
}

jscLib.system.EApplication = jscLib.libFuncs.subclass(jscLib.system.Exception, "Application Exception");
jscLib.system.EApplication.create = function(){}

jscLib.system.ECustom = jscLib.libFuncs.subclass(jscLib.system.Exception, "Custom Exception");
jscLib.system.ECustom.create = function(){}

RaiseException = function(obj){
  throw obj;
}