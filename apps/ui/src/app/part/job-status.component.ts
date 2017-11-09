import {Component, Input, OnChanges, SimpleChanges} from '@angular/core';
import {JobStatus} from "../shared/job-status";

class Status {
    constructor(public id: string, public title: string, public badgeModifier: string) {}
}

const statuses = [
  new Status(JobStatus.NEW, 'New', 'badge-default'),
  new Status(JobStatus.RUNNING, 'Running', 'badge-info'),
  new Status(JobStatus.RUNNING_SUB_JOBS, 'Running sub jobs', 'badge-info'),
  new Status(JobStatus.RETRYING, 'Retrying', 'badge-info'),
  new Status(JobStatus.CANCELED, 'Cancelled', 'badge-default'),
  new Status(JobStatus.COMPLETED, 'Completed', 'badge-success'),
  new Status(JobStatus.FAILED, 'Failed', 'badge-danger'),
  new Status(JobStatus.TERMINATED, 'Terminated', 'badge-default'),
];

@Component({
  selector: 'job-status',
  template: `<span class="w-100 text-center badge {{ statusObj.badgeModifier }}">{{ statusObj.title }}</span>`,
})
export class JobStatusComponent implements OnChanges {
    @Input() status: string;
    statusObj: Status;

    ngOnChanges(changes: SimpleChanges): void {
        let currentStatus: Status = statuses.filter((status: Status) => status.id === this.status).shift();

        if (!currentStatus) {
          currentStatus = new Status(this.status, this.status, 'badge-default');
        }

        this.statusObj = currentStatus;
    }
}
