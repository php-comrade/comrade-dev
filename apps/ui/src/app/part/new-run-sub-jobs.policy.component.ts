import {Component, EventEmitter, Output} from '@angular/core';
import {RunSubJobsPolicy} from "../shared/run-sub-jobs-policy";

@Component({
  selector: 'new-run-sub-jobs-policy',
  template: `
      <h6>New run sub jobs policy:</h6>
      <div class="form-group">
          <label for="on-duplicate-run">On Failed Sub Job:</label>
          <select class="form-control" id="on-failed-sub-job" required [(ngModel)]="policy.onFailedSubJob" name="on-failed-sub-job">
              <option value="mark_job_as_failed">Mark job as failed</option>
              <option value="mark_job_as_completed">Mark job as completed</option>
          </select>
      </div>

      <a href="javascript:void(0)" (click)="add()">Add</a>
  `,
})
export class NewRunSubJobsPolicyComponent {
    @Output() onPolicyAdded = new EventEmitter<RunSubJobsPolicy>();

    policy: RunSubJobsPolicy;

    constructor() {
        this.policy = new RunSubJobsPolicy();
        this.policy.onFailedSubJob = 'mark_job_as_failed';
    }

    add() {
        this.onPolicyAdded.emit(this.policy);
    }
}
