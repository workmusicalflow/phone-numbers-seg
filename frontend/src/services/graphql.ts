import { gql } from '@apollo/client/core';

// User Queries
export const LOGIN_MUTATION = gql`
  mutation Login($username: String!, $password: String!) {
    login(username: $username, password: $password)
  }
`;

export const ME_QUERY = gql`
  query GetCurrentUser {
    me {
      id
      username
      email
      smsCredit
      smsLimit
      isAdmin
      createdAt
      updatedAt
    }
  }
`;

// Contact Queries
export const CONTACTS_LIST = gql`
  query GetContacts($limit: Int, $offset: Int, $search: String, $groupId: ID) {
    contacts(limit: $limit, offset: $offset, search: $search, groupId: $groupId) {
      id
      name
      phoneNumber
      email
      createdAt
      # Add SMS score for badge display
      smsTotalCount
      smsScore
    }
    contactsCount(search: $search, groupId: $groupId)
  }
`;

export const CONTACT_DETAILS = gql`
  query GetContact($id: ID!) {
    contact(id: $id) {
      id
      name
      phoneNumber
      email
      notes
      createdAt
      updatedAt
      groups {
        id
        name
      }
      # Add new SMS fields
      smsTotalCount
      smsSentCount
      smsFailedCount
      smsScore
    }
  }
`;

export const CONTACT_SMS_HISTORY = gql`
  query GetContactSMSHistory($id: ID!, $limit: Int, $offset: Int) {
    contact(id: $id) {
      id
      phoneNumber
      smsHistory(limit: $limit, offset: $offset) {
        id
        status
        message
        createdAt
        errorMessage
        senderName
        messageId
        senderAddress
      }
    }
  }
`;

export const CREATE_CONTACT = gql`
  mutation CreateContact($name: String!, $phoneNumber: String!, $email: String, $notes: String, $groupIds: [ID!]) {
    createContact(name: $name, phoneNumber: $phoneNumber, email: $email, notes: $notes, groupIds: $groupIds) {
      id
      name
      phoneNumber
      email
      notes
      createdAt
      updatedAt
    }
  }
`;

export const UPDATE_CONTACT = gql`
  mutation UpdateContact($id: ID!, $name: String!, $phoneNumber: String!, $email: String, $notes: String, $groupIds: [ID!]) {
    updateContact(id: $id, name: $name, phoneNumber: $phoneNumber, email: $email, notes: $notes, groupIds: $groupIds) {
      id
      name
      phoneNumber
      email
      notes
      createdAt
      updatedAt
    }
  }
`;

export const DELETE_CONTACT = gql`
  mutation DeleteContact($id: ID!) {
    deleteContact(id: $id)
  }
`;

// Contact Group Queries
export const CONTACT_GROUPS = gql`
  query GetContactGroups($limit: Int, $offset: Int) {
    contactGroups(limit: $limit, offset: $offset) {
      id
      name
      description
      contactCount
      createdAt
      updatedAt
    }
    contactGroupsCount
  }
`;

export const CONTACT_GROUP_DETAILS = gql`
  query GetContactGroup($id: ID!) {
    contactGroup(id: $id) {
      id
      name
      description
      contactCount
      createdAt
      updatedAt
    }
  }
`;

export const CONTACTS_IN_GROUP = gql`
  query GetContactsInGroup($groupId: ID!, $limit: Int, $offset: Int) {
    contactsInGroup(groupId: $groupId, limit: $limit, offset: $offset) {
      id
      name
      phoneNumber
      email
      createdAt
      updatedAt
      # Add SMS score for badge display
      smsTotalCount
      smsScore
    }
    contactsInGroupCount(groupId: $groupId)
  }
`;

// SMS Queries and Mutations
export const SEND_SMS = gql`
  mutation SendSMS($phoneNumber: String!, $message: String!) {
    sendSms(phoneNumber: $phoneNumber, message: $message) {
      id
      phoneNumber
      message
      status
      createdAt
    }
  }
`;

export const SEND_BULK_SMS = gql`
  mutation SendBulkSMS($phoneNumbers: [String!]!, $message: String!) {
    sendBulkSms(phoneNumbers: $phoneNumbers, message: $message) {
      status
      message
      summary {
        total
        successful
        failed
      }
      results {
        phoneNumber
        status
        message
      }
    }
  }
`;

export const SMS_HISTORY = gql`
  query GetSMSHistory($limit: Int, $offset: Int, $userId: ID, $status: String, $search: String, $segmentId: ID) {
    smsHistory(limit: $limit, offset: $offset, userId: $userId, status: $status, search: $search, segmentId: $segmentId) {
      id
      phoneNumber
      message
      status
      messageId
      errorMessage
      senderAddress
      senderName
      createdAt
    }
    smsHistoryCount(userId: $userId, status: $status, search: $search, segmentId: $segmentId)
  }
`;