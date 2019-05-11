

class Form{
    /**
     * Takes an obj of the inputs you would normally see on the form element attributes
     * Example.  
     *  {"id": "myform", 
     * "class": "form", 
     * "action": "#", 
     * "method": "post"
     * }
     */
    constructor(obj){
        this.id = obj.id,
        this.class = obj.class,
        this.action = obj.action,
        this.method = obj.method
    }//constructor

    /**
     * Takes a string of elements and returns it. 
     * Example of use would be to create all the form inputs in html as string format
     * then to insert them into the page with innerHTML. 
     */

    setForm(innerElements=""){
        return "<form id=\"" + this.id + "\" class=\"" + this.class + "\" action=\"" + this.action + "\" method=\"" + this.method + "\">" + innerElements + "</form>";
    }//setForm


    /**
     * Takes in an obj and returns it as a string. 
     * Example obj:
     *      textInput = {
                "id": "id1",
                "label": "This is my label",
                "class": "form-control",
                "type": "text",
                "name": "myinput",
                "placeholder": "This is where you write something"
                };
     * 
     */
    createTextInput(obj){
        return "<label for=\"" + obj.id + "\">" + obj.label + "</label><input id=\"" + obj.id + "\" class=\"" + obj.class + "\" type=\"" + obj.type + "\" name=\"" + obj.name + "\" data-label=\"" + obj.label + "\" placeholder=\"" + obj.placeholder + "\">";
    }//createTextInput

    /*
        Takes in an array of objects and places them into radio buttons and returns them. 
        Example Array: 

        radio = [{
                "label": "Yes",
                "id": "id1",
                "class": "radio",
                "type": "radio",
                "name": "radio1",
                "default": "checked"
                }];
    */
    createRadioInput(arr){
        var res = "";
        //
        if(arr.length > 0){
            
            for(var i=0; i < arr.length; i++){
                //check if we have a default value
                if(arr[i].default == "checked"){
                    res += "<label for=\"" + arr[i].id + "\"><input id=\"" + arr[i].id + "\" class=\"" + arr[i].class + "\" type=\"" + arr[i].type + "\" name=\"" + arr[i].name + "\" data-label=\"" + arr[i].label + "\"  " + arr[i].default + ">" + arr[i].label + "</label>";
                }else{
                    res += "<label for=\"" + arr[i].id + "\"><input id=\"" + arr[i].id + "\" class=\"" + arr[i].class + "\" type=\"" + arr[i].type + "\" name=\"" + arr[i].name + "\" data-label=\"" + arr[i].label + "\" >" + arr[i].label + "</label>";
                }
                
            }
        }

        return res;
    }//createRadioInput


        /*
        Takes in an array of objects and places them into checkboxes and returns them. 
        Example Array: 

        radio = [{
                "label": "Yes",
                "id": "id1",
                "class": "checkbox",
                "type": "checkbox",
                "name": "",
                "default": "checked"
                }];
    */
    createCheckboxInput(arr){
        var res = "";

        if(arr.length > 0){
            for(var i=0; i < arr.length; i++){
                if(arr[i].default == "checked"){
                    res += "<label for=\"" + arr[i].id + "\"><input id=\"" + arr[i].id + "\" class=\"" + arr[i].class + "\" type=\"" + arr[i].type + "\" name=\"" + arr[i].name + "\" data-label=\"" + arr[i].label + "\" " + arr[i].default + ">" + arr[i].label + "</label>";
                }else{
                    res += "<label for=\"" + arr[i].id + "\"><input id=\"" + arr[i].id + "\" class=\"" + arr[i].class + "\" type=\"" + arr[i].type + "\" name=\"" + arr[i].name + "\" data-label=\"" + arr[i].label + "\">" + arr[i].label + "</label>";
                }
            }
        }
        return res;
    }

    /**
     * Takes in an obj and returns an html string
     * Example obj: 
     *         let select = {
                    "id" : "id8",
                    "label": "The Selector of Legend!",
                    "class": "selector",
                    "name" : "myselector",
                    "options": a.createOptions(options)          
                    }

     */
    createSelection(obj){
        return "<label for=\"" + obj.id + "\">" + obj.label + " </label><select id=\"" + obj.id + "\" class=\"" + obj.class + "\" name=\"" + obj.name + "\">" + obj.options + "</select>";
    }

    /**
     * Takes in an array of options and returns the options for the createSelection 
     * Example: 
     * let options = ["Select", "Phone", "Pin and Pass", "Email"];
     * 
     */
    createOptions(arr){
        var res = "";

        if(arr.length > 0){
            for(var i = 0; i < arr.length; i++){
                res += "<option value=\"val" + i + "\">" + arr[i] + "</option>";
            }
        }
        
        return res;
    }

    /**
     * Takes and obj and returns an html string of Textarea
     * Example obj: 
     *         let textArea = {
                    "label": "Textarea of Legend!",
                    "id": "id9",
                    "class": "form-control",
                    "name" : "mytextarea",
                    "placeholder": "Placeholder of Legned!"
                    }; 
     */

    createTextArea(obj){
        return "<label for=\"" + obj.id + "\">" + obj.label + "</label><textarea id=\"" + obj.id + "\" class=\"" + obj.class + "\" name=\"" + obj.name + "\" data-label=\"" + obj.label + "\" placeholder=\"" + obj.placeholder + "\"></textarea>"
    }

    /**
     * createButton is unique to this class because it also has the ability to choose what it will do
     * There will be an option to copy to the clipboard for later use or save to a preset folder or both. 
     * Example obj: 
     *  
     */
    createButton(obj){
        return "<button id=\"" + obj.id + "\" class=\"" + obj.class + "\" type=\"" + obj.type + "\" name=\"" + obj.name + "\" data-btn-type=\"" + obj.btntype + "\">" + obj.text + "</button>";
    }


/**
 * This creates a fieldset and legend around elements such as radios and checkboxes
 * Example: 
 *       let legend ={
        "id" : "id4",
        "class": "fieldset",
        "legend": "This is what the Legend Will Say!",
        "elements" : a.createRadioInput(radio)
      }

      NOTE: The elements can be created on the fly and then stored to run. 
 * 
 */


    createLegend(obj){
        return "<fieldset id=\"" + obj.id + "\" class=\"" + obj.class + "\"><legend>" + obj.legend + "</legend> " + obj.elements + "</fieldset>"
    }


}