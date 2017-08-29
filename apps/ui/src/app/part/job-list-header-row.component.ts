import { Component } from '@angular/core';

@Component({
  selector: '[job-list-header-row]',
  template: `
      <th>Id:</th>
      <th>Name:</th>
      <th>Created at</th>
      <th>Updated at</th>
      <th>Status</th>
      <th></th>
  `,
})
export class JobListHeaderRowComponent {
}
