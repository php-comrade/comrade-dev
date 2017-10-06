import {Component, EventEmitter, Output} from '@angular/core';
import {SubJobPolicy} from "../shared/sub-job-policy";

@Component({
  selector: 'policy-new-sub-job',
  template: `
      <h6>New sub job policy:</h6>
      <div class="form-group">
          <label for="sub-job-policy-parent-id">ParentId:</label>
          <input class="form-control" id="sub-job-policy-parent-id" required [(ngModel)]="policy.parentId">
      </div>

      <a href="javascript:void(0)" (click)="add()">Add</a>
  `,
})
export class PolicyNewSubJobComponent {
    @Output() onPolicyAdded = new EventEmitter<SubJobPolicy>();

    policy: SubJobPolicy;

    constructor() {
        this.policy = new SubJobPolicy();
    }

    add() {
        this.onPolicyAdded.emit(this.policy);
    }
}
