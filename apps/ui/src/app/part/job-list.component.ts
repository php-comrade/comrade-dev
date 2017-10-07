import {Component, Input} from '@angular/core';
import {Job} from "../shared/job";

@Component({
  selector: 'job-list',
  template: `
      <table class="table table-striped table-hover table-bordered">
          <thead>
            <tr job-list-header-row></tr>
          </thead>
          <tbody>
            <tr *ngFor="let job of jobs" job-list-row [viewButton]="viewButton" [job]="job"></tr>
          </tbody>
      </table>
  `,
})
export class JobListComponent{
    @Input() jobs: Job[];
    @Input() viewButton: false;
}
