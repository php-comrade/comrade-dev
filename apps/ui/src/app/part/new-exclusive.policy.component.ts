import {Component, EventEmitter, Output} from '@angular/core';
import {ExclusivePolicy} from "../shared/exclusive-policy";

@Component({
  selector: 'new-exclusive-policy',
  template: `
      <h6>New exclusive policy:</h6>
      <div class="form-group">
          <label for="on-duplicate-run">On Duplicate Run:</label>
          <select class="form-control" id="on-duplicate-run" required [(ngModel)]="policy.onDuplicateRun" name="on-duplicate-run">
              <option value="mark_job_as_canceled">Mark job as canceled</option>
              <option value="mark_job_as_failed">Mark job as failed</option>
          </select>
      </div>

      <a href="javascript:void(0)" (click)="add()">Add</a>
  `,
})
export class NewExclusivePolicyComponent {
    @Output() onPolicyAdded = new EventEmitter<ExclusivePolicy>();

    policy: ExclusivePolicy;

    constructor() {
        this.policy = new ExclusivePolicy;
        this.policy.onDuplicateRun = 'mark_job_as_canceled';
    }

    add() {
        this.onPolicyAdded.emit(this.policy);
    }
}
