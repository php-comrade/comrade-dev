import {Component, Input} from '@angular/core';
import {Job} from "../shared/job";

@Component({
  selector: '[job-list-row]',
  template: `
      <td>{{job.id}}</td>
      <td>{{job.name}}</td>
      <td><time-ago [date]="job.createdAt"></time-ago></td>
      <td><time-ago [date]="job.updatedAt"></time-ago></td>
      <td>
          <job-status [status]="job.currentResult.status"></job-status>
      </td>
      <td>
          <button *ngIf="viewButton" type="button" class="btn btn-primary btn-sm" [routerLink]="['/job', job.id, 'view' ]">View</button>
      </td>
  `,
})
export class JobListRowComponent{
    @Input() job: Job;
    @Input() viewButton: false;
}
