import { Component, Input, forwardRef } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR, NG_VALIDATORS, FormControl, Validator } from '@angular/forms';

@Component({
  selector: 'json-input',
  template:
        `
      <textarea
              [value]="jsonString"
              (change)="onChange($event)"
              (keyup)="onChange($event)"
              class="form-control">
        
        </textarea>
  `,
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => JsonInputComponent),
      multi: true,
    },
    {
      provide: NG_VALIDATORS,
      useExisting: forwardRef(() => JsonInputComponent),
      multi: true,
    }]
})
export class JsonInputComponent implements ControlValueAccessor, Validator {
  jsonString: string;
  parseError: boolean;
  data: any;

  // this is the initial value set to the component
  public writeValue(obj: any) {
    if (obj) {
      this.data = obj;
      // this will format it with 4 character spacing
      this.jsonString = JSON.stringify(this.data, undefined, 4);
    } else {
      this.data = '';
      this.jsonString = '';
    }
  }

  // registers 'fn' that will be fired wheb changes are made
  // this is how we emit the changes back to the form
  public registerOnChange(fn: any) {
    this.propagateChange = fn;
  }

  // validates the form, returns null when valid else the validation object
  // in this case we're checking if the json parsing has passed or failed from the onChange method
  public validate(c: FormControl) {
    return (!this.parseError) ? null : {
      jsonParseError: {
        valid: false,
      },
    };
  }

  // not used, used for touch input
  public registerOnTouched() { }

  // change events from the textarea
  onChange(event) {

    // get value from text area
    let newValue = event.target.value;

    try {
      // parse it to json
      this.data = JSON.parse(newValue);
      this.parseError = false;
    } catch (ex) {
      // set parse error if it fails
      this.parseError = true;
    }

    // update the form
    this.propagateChange(this.data);
  }

  // the method set in registerOnChange to emit changes back to the form
  private propagateChange = (_: any) => { };
}