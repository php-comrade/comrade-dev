import {Component, EventEmitter, Output} from '@angular/core';
import {Runner} from "../shared/runner";
import {HttpRunner} from "../shared/http-runner";

@Component({
  selector: 'runner-new-http',
  template: `
      <div class="form-group">
          <label for="runner-http-url">Url:</label>
          <input type="url" class="form-control" id="runner-http-url" required [(ngModel)]="url" name="runner-http-url">
      </div>

      <a href="javascript:void(0)" (click)="addRunner()">Add</a>
  `
})
export class RunnerNewHttpComponent {
    @Output() onRunnerAdded = new EventEmitter<Runner>();

    url: string;

    addRunner() {
        let runner = new HttpRunner();
        runner.url = this.url;

        this.onRunnerAdded.emit(runner);
    }
}
