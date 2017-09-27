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
      <div class="form-group">
          <label for="runner-http-sync">Sync:</label>
          <input type="checkbox" class="form-control" id="runner-http-sync" [(ngModel)]="sync" name="runner-http-sync">
      </div>

      <a href="javascript:void(0)" (click)="addRunner()">Add</a>
  `
})
export class RunnerNewHttpComponent {
    @Output() onRunnerAdded = new EventEmitter<Runner>();

    url: string;
    sync: boolean = false;

    addRunner() {
        let runner = new HttpRunner();
        runner.url = this.url;
        runner.sync = this.sync;

        this.onRunnerAdded.emit(runner);
    }
}
