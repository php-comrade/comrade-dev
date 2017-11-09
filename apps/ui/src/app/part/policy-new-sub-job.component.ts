import {Component, EventEmitter, Output} from '@angular/core';
import {SubJobPolicy} from "../shared/sub-job-policy";
import {JobTemplate} from "../shared/job-template";

@Component({
  selector: 'policy-new-sub-job',
  template: `
      <h6>New sub job policy:</h6>
      <div class="form-group">
          <label for="sub-job-policy-parent-id">ParentId:</label>
          <template-search (onUnSelected)="unselected()" (onSelected)="selected($event)"></template-search>
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

    public unselected(): void {
      this.policy.parentId = null;
    }

    public selected(template: JobTemplate): void
    {
      this.policy.parentId = template.templateId;
    }
}
